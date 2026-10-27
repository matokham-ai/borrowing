<?php

namespace App\Services\Payments;

use App\Models\Borrower;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\LoanSchedule;
use App\Models\PaymentAllocation;
use App\Services\Loans\LoanService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PaymentAllocationService
 *
 * Applies a posted LoanPayment to outstanding schedule rows using a
 * configurable waterfall strategy.
 *
 * Default waterfall order: penalty → fees → interest → principal
 * Override per loan product via loan_products.payment_waterfall JSON column.
 *
 * Key behaviours
 * ──────────────
 *  • Partial payments  — allocates as much as the payment covers; schedule rows
 *                        remain in 'partial' status until fully settled.
 *  • Overpayments      — surplus that exceeds all outstanding obligations is
 *                        stored in loan_payments.unapplied_cents and added to
 *                        borrowers.unapplied_balance_cents for future application.
 *  • Idempotent        — calling allocate() on an already-allocated payment
 *                        is a no-op (existing allocations are detected).
 *  • Immutable history — allocations are never updated; reversals are handled
 *                        by PaymentReversalService which creates counter-entries.
 *
 * After allocation, LoanBalanceService must be called to recompute schedule
 * statuses and the loan's overall status.
 */
class PaymentAllocationService
{
    /** Fallback waterfall when nothing is configured on the product. */
    private const DEFAULT_WATERFALL = ['penalty', 'fees', 'interest', 'principal'];

    /** Map allocation_type → schedule column names [due, paid]. */
    private const COMPONENT_COLUMNS = [
        'penalty'   => ['due' => 'penalty_due_cents',   'paid' => 'penalty_paid_cents'],
        'fees'      => ['due' => 'fees_due_cents',       'paid' => 'fees_paid_cents'],
        'interest'  => ['due' => 'interest_due_cents',   'paid' => 'interest_paid_cents'],
        'principal' => ['due' => 'principal_due_cents',  'paid' => 'principal_paid_cents'],
    ];

    public function __construct(private readonly LoanService $loanService) {}

    // ── Public API ───────────────────────────────────────────────────────

    /**
     * Allocate a posted LoanPayment across outstanding schedule rows.
     *
     * Returns the list of PaymentAllocation records created.
     *
     * @return PaymentAllocation[]
     * @throws \RuntimeException when the payment is not in 'posted' status.
     */
    public function allocate(LoanPayment $payment): array
    {
        if ($payment->status !== 'posted') {
            throw new \RuntimeException(
                "Cannot allocate: LoanPayment #{$payment->id} has status '{$payment->status}', expected 'posted'."
            );
        }

        // Idempotency: if allocations already exist for this payment, skip
        if (PaymentAllocation::where('loan_payment_id', $payment->id)->exists()) {
            Log::info('PaymentAllocationService: payment already allocated, skipping', [
                'loan_payment_id' => $payment->id,
            ]);
            return PaymentAllocation::where('loan_payment_id', $payment->id)->get()->all();
        }

        return DB::transaction(function () use ($payment) {
            $loan     = Loan::with('loanProduct')->findOrFail($payment->loan_id);
            $this->loanService->ensureSchedule($loan);
            $waterfall = $this->resolveWaterfall($loan);
            $remaining = (int) $payment->amount_cents;
            $created   = [];

            // Load all outstanding schedule rows in due-date order
            $schedules = LoanSchedule::where('organisation_id', $payment->organisation_id)
                ->where('loan_id', $payment->loan_id)
                ->whereIn('status', ['pending', 'partial', 'overdue'])
                ->orderBy('due_date')
                ->orderBy('installment_number')
                ->get();

            foreach ($schedules as $schedule) {
                if ($remaining <= 0) {
                    break;
                }

                $scheduleRemaining = max(
                    0,
                    (int) $schedule->total_due_cents
                    + (int) $schedule->penalty_due_cents
                    - (int) $schedule->total_paid_cents
                );

                foreach ($waterfall as $component) {
                    if ($remaining <= 0 || $scheduleRemaining <= 0) {
                        break;
                    }

                    $outstanding = $this->outstandingFor($schedule, $component);

                    if ($outstanding <= 0) {
                        continue;
                    }

                    // How much of this component can we satisfy?
                    $toApply = min($remaining, $outstanding, $scheduleRemaining);

                    $created[] = PaymentAllocation::create([
                        'organisation_id' => $payment->organisation_id,
                        'loan_payment_id' => $payment->id,
                        'loan_schedule_id'=> $schedule->id,
                        'allocation_type' => $component,
                        'amount_cents'    => $toApply,
                    ]);

                    $remaining -= $toApply;
                    $scheduleRemaining -= $toApply;
                }
            }

            // ── Handle overpayment / unapplied surplus ────────────────────
            $unapplied = max(0, $remaining);

            if ($unapplied > 0) {
                Log::info('PaymentAllocationService: overpayment detected', [
                    'loan_payment_id'  => $payment->id,
                    'unapplied_cents'  => $unapplied,
                    'borrower_id'      => $payment->borrower_id,
                ]);

                // Record on the payment itself
                $payment->unapplied_cents = $unapplied;
                $payment->save();

                // Add to borrower credit balance (atomic increment)
                if ($payment->borrower_id) {
                    Borrower::where('id', $payment->borrower_id)
                        ->increment('unapplied_balance_cents', $unapplied);
                }
            }

            return $created;
        });
    }

    /**
     * Apply any existing unapplied balance from the borrower's credit wallet
     * to their next outstanding schedule instalment.
     *
     * This is called after allocating a new payment, and also at the start
     * of each billing cycle to sweep credits forward.
     *
     * Returns the new virtual LoanPayment created from the credit (or null
     * if there was nothing to apply or no outstanding instalments).
     */
    public function applyUnappliedBalance(Borrower $borrower, int $loanId): ?LoanPayment
    {
        $creditCents = (int) $borrower->unapplied_balance_cents;

        if ($creditCents <= 0) {
            return null;
        }

        $hasOutstanding = LoanSchedule::where('organisation_id', $borrower->organisation_id)
            ->where('loan_id', $loanId)
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->exists();

        if (!$hasOutstanding) {
            return null;
        }

        return DB::transaction(function () use ($borrower, $loanId, $creditCents) {
            // Deduct from borrower wallet
            Borrower::where('id', $borrower->id)
                ->decrement('unapplied_balance_cents', $creditCents);

            // Create a virtual payment from the credit balance
            /** @var LoanPayment $payment */
            $payment = LoanPayment::create([
                'organisation_id'     => $borrower->organisation_id,
                'loan_id'             => $loanId,
                'borrower_id'         => $borrower->id,
                'mpesa_transaction_id'=> null,
                'payment_method'      => 'credit_balance',
                'amount_cents'        => $creditCents,
                'unapplied_cents'     => 0,
                'status'              => 'posted',
                'received_at'         => now(),
                'posted_at'           => now(),
            ]);

            // Now allocate this virtual payment
            $this->allocate($payment);

            return $payment;
        });
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    /**
     * Compute the outstanding (un-paid) amount for a specific component
     * on a schedule row.
     */
    private function outstandingFor(LoanSchedule $schedule, string $component): int
    {
        $cols = self::COMPONENT_COLUMNS[$component] ?? null;

        if (!$cols) {
            return 0;
        }

        $due  = (int) ($schedule->{$cols['due']}  ?? 0);
        $paid = (int) ($schedule->{$cols['paid']} ?? 0);

        return max(0, $due - $paid);
    }

    /**
     * Resolve the allocation waterfall for a given loan.
     *
     * Priority: product-level config → DEFAULT_WATERFALL.
     * Invalid component names are silently filtered out.
     *
     * @return string[]
     */
    private function resolveWaterfall(Loan $loan): array
    {
        $product   = $loan->loanProduct;
        $waterfall = $product?->payment_waterfall;

        if (!empty($waterfall) && is_array($waterfall)) {
            $valid = array_filter(
                $waterfall,
                fn (string $c) => isset(self::COMPONENT_COLUMNS[$c])
            );

            if (!empty($valid)) {
                return array_values($valid);
            }
        }

        return self::DEFAULT_WATERFALL;
    }
}
