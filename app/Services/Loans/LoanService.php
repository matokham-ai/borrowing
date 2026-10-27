<?php

namespace App\Services\Loans;

use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\LoanSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * LoanService
 *
 * Creates a Loan and its repayment schedule from an approved LoanApplication.
 * Does NOT trigger disbursement — that is handled by MpesaDisbursementService.
 *
 * Schedule generation supports:
 *   - daily   — one instalment per day (micro-credit, short tenors)
 *   - weekly  — one instalment per week
 *   - monthly — one instalment per month (standard amortising)
 *
 * For all frequencies the fee and interest already baked into the loan terms
 * are spread across instalments proportionally (last instalment absorbs any
 * rounding remainder).
 */
class LoanService
{
    /**
     * Prefix for the human-readable repayment reference.
     * Format: MAV-XXXXXXXX (zero-padded loan id, 8 digits).
     */
    private const REFERENCE_PREFIX = 'MAV';

    // ── Public API ───────────────────────────────────────────────────────

    /**
     * Create a loan + schedule from an approved application.
     *
     * The application must be in status = 'approved'.
     * A loan must not already exist for this application.
     *
     * Returns the newly created Loan (with schedules loaded).
     *
     * @throws \RuntimeException  When preconditions are not met.
     * @throws \Throwable         Re-thrown on DB transaction failure.
     */
    public function createFromApplication(LoanApplication $application): Loan
    {
        // ── Preconditions ────────────────────────────────────────────────
        if ($application->status !== 'approved') {
            throw new \RuntimeException(
                "Cannot create loan: application #{$application->id} is in status '{$application->status}', expected 'approved'."
            );
        }

        if (
            !$application->first_level_approved_by
            || !$application->final_approved_by
            || (int) $application->first_level_approved_by === (int) $application->final_approved_by
            || (int) $application->created_by === (int) $application->first_level_approved_by
            || (int) $application->created_by === (int) $application->final_approved_by
        ) {
            throw new \RuntimeException(
                "Cannot create loan: application #{$application->id} requires distinct originator, first-level and final approvers."
            );
        }

        if (
            !$application->security_requirements_required
            || !$application->hasCompleteSecurityPackage()
        ) {
            throw new \RuntimeException(
                "Cannot create loan: application #{$application->id} is missing required guarantor, consent or collateral evidence."
            );
        }

        if (Loan::where('loan_application_id', $application->id)->exists()) {
            throw new \RuntimeException(
                "Loan already exists for application #{$application->id}."
            );
        }

        $product = $application->loanProduct;

        if (!$product) {
            throw new \RuntimeException(
                "Loan product not found for application #{$application->id}."
            );
        }

        // ── Compute loan financial terms ──────────────────────────────────
        $principalCents = (int) $application->approved_amount_cents;
        $termDays       = (int) $application->approved_term_days;
        $interestRate   = (float) $product->interest_rate;          // e.g. 0.1500 = 15 %

        // Flat-rate interest: principal × rate (regardless of term for short-term products).
        // Adjust here when you add amortising / reducing-balance support.
        $interestCents = (int) round($principalCents * $interestRate);

        // Fee: fixed fee + percentage fee (if configured)
        $feeCents = (int) $product->fixed_fee_cents;
        if ((float) $product->fee_rate > 0) {
            $feeCents += (int) round($principalCents * (float) $product->fee_rate);
        }

        $totalPayableCents = $principalCents + $interestCents + $feeCents;

        return DB::transaction(function () use (
            $application, $product,
            $principalCents, $interestCents, $feeCents, $totalPayableCents, $termDays
        ) {
            // ── Create the Loan ──────────────────────────────────────────
            $loan = Loan::create([
                'organisation_id'      => $application->organisation_id,
                'borrower_id'          => $application->borrower_id,
                'loan_application_id'  => $application->id,
                'loan_product_id'      => $application->loan_product_id,
                'loan_tier_rule_id'    => $application->loan_tier_rule_id,
                'principal_cents'      => $principalCents,
                'interest_rate'        => $product->interest_rate,
                'interest_cents'       => $interestCents,
                'fees_cents'           => $feeCents,
                'total_payable_cents'  => $totalPayableCents,
                'term_days'            => $termDays,
                'repayment_frequency'  => $product->repayment_frequency,
                'status'               => 'disbursement_pending',
                'approved_at'          => $application->decided_at ?? now(),
            ]);

            // ── Stamp the unique repayment reference ─────────────────────
            $loan->repayment_reference = self::REFERENCE_PREFIX . '-' . str_pad((string) $loan->id, 8, '0', STR_PAD_LEFT);
            $loan->save();

            // ── Generate the repayment schedule ──────────────────────────
            $this->generateSchedule($loan, $product->repayment_frequency, $termDays);

            // ── Update the application status ────────────────────────────
            $application->update(['status' => 'loan_created']);

            return $loan->load('schedules');
        });
    }

    /** Create a schedule for legacy or imported loans that do not have one yet. */
    public function ensureSchedule(Loan $loan): Loan
    {
        if ($loan->schedules()->exists()) {
            return $loan->load('schedules');
        }

        return DB::transaction(function () use ($loan) {
            $this->generateSchedule(
                $loan,
                (string) $loan->repayment_frequency,
                (int) $loan->term_days,
            );

            return $loan->load('schedules');
        });
    }

    // ── Schedule generation ──────────────────────────────────────────────

    /**
     * Build and insert all LoanSchedule rows for a Loan.
     *
     * All monetary rounding remainders are absorbed by the last instalment
     * so that sum(schedules.*_due_cents) == loan.*_cents exactly.
     */
    private function generateSchedule(Loan $loan, string $frequency, int $termDays): void
    {
        $installments = $this->buildInstallments(
            startDate:    now()->toDateString(),
            frequency:    $frequency,
            termDays:     $termDays,
            principal:    (int) $loan->principal_cents,
            interest:     (int) $loan->interest_cents,
            fees:         (int) $loan->fees_cents,
        );

        $rows = [];
        $now  = now()->toDateTimeString();

        foreach ($installments as $idx => $inst) {
            $rows[] = [
                'organisation_id'      => $loan->organisation_id,
                'loan_id'              => $loan->id,
                'installment_number'   => $idx + 1,
                'due_date'             => $inst['due_date'],
                'principal_due_cents'  => $inst['principal'],
                'interest_due_cents'   => $inst['interest'],
                'fees_due_cents'       => $inst['fees'],
                'penalty_due_cents'    => 0,
                'total_due_cents'      => $inst['principal'] + $inst['interest'] + $inst['fees'],
                'principal_paid_cents' => 0,
                'interest_paid_cents'  => 0,
                'fees_paid_cents'      => 0,
                'penalty_paid_cents'   => 0,
                'total_paid_cents'     => 0,
                'status'               => 'pending',
                'paid_at'              => null,
                'created_at'           => $now,
                'updated_at'           => $now,
            ];
        }

        LoanSchedule::insert($rows);
    }

    /**
     * Compute the instalment breakdown: dates + component amounts.
     *
     * @return array<int, array{due_date: string, principal: int, interest: int, fees: int}>
     */
    private function buildInstallments(
        string $startDate,
        string $frequency,
        int    $termDays,
        int    $principal,
        int    $interest,
        int    $fees,
    ): array {
        $dueDate   = Carbon::parse($startDate);
        $count     = $this->installmentCount($frequency, $termDays);

        // Per-instalment amounts (floor division; remainder applied to last)
        $principalPer = (int) floor($principal / $count);
        $interestPer  = (int) floor($interest  / $count);
        $feesPer      = (int) floor($fees      / $count);

        $principalRem = $principal - ($principalPer * $count);
        $interestRem  = $interest  - ($interestPer  * $count);
        $feesRem      = $fees      - ($feesPer      * $count);

        $installments = [];

        for ($i = 0; $i < $count; $i++) {
            $dueDate = $this->advanceDueDate($dueDate, $frequency, $i);

            $isLast  = ($i === $count - 1);

            $installments[] = [
                'due_date'  => $dueDate->toDateString(),
                'principal' => $principalPer + ($isLast ? $principalRem : 0),
                'interest'  => $interestPer  + ($isLast ? $interestRem  : 0),
                'fees'      => $feesPer      + ($isLast ? $feesRem      : 0),
            ];
        }

        return $installments;
    }

    /**
     * Advance a date by one instalment period.
     * The $index = 0 call moves from start to first due date.
     */
    private function advanceDueDate(Carbon $base, string $frequency, int $index): Carbon
    {
        // Clone to avoid mutating the same object across iterations
        $date = $index === 0 ? $base->copy() : $base;

        return match ($frequency) {
            'daily'   => $date->addDay(),
            'weekly'  => $date->addWeek(),
            'monthly' => $date->addMonth(),
            default   => $date->addDay(),
        };
    }

    /**
     * Compute total number of instalments for the given frequency + term.
     */
    private function installmentCount(string $frequency, int $termDays): int
    {
        return match ($frequency) {
            'daily'   => max(1, $termDays),
            'weekly'  => max(1, (int) ceil($termDays / 7)),
            'monthly' => max(1, (int) ceil($termDays / 30)),
            default   => max(1, $termDays),
        };
    }
}
