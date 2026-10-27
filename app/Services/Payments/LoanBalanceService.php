<?php

namespace App\Services\Payments;

use App\Models\Borrower;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\LoanSchedule;
use Illuminate\Support\Facades\DB;

/**
 * LoanBalanceService
 *
 * After PaymentAllocationService creates payment_allocations, this service:
 *
 *  1. Refreshes each affected loan_schedule's paid columns by summing its
 *     allocations, then derives the schedule row status.
 *  2. Derives the overall loan status from the aggregate of all schedules.
 *  3. Updates borrowers.last_repayment_at.
 *
 * Design note: we always derive status from data rather than storing it in
 * two places.  The *_paid_cents columns on loan_schedules are the single
 * source of truth — the status columns are derived views.
 */
class LoanBalanceService
{
    // Schedule status values
    private const STATUS_PENDING  = 'pending';
    private const STATUS_PARTIAL  = 'partial';
    private const STATUS_PAID     = 'paid';
    private const STATUS_OVERDUE  = 'overdue';

    // ── Public API ───────────────────────────────────────────────────────

    /**
     * Refresh schedule paid-amounts + statuses for all schedules on a loan,
     * then recompute the loan's own status.
     *
     * Call this after every PaymentAllocationService::allocate() run.
     */
    public function refreshAfterPayment(LoanPayment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $loanId  = (int) $payment->loan_id;
            $orgId   = (int) $payment->organisation_id;

            // ── 1. Re-aggregate every schedule row on this loan ───────────
            $schedules = LoanSchedule::where('organisation_id', $orgId)
                ->where('loan_id', $loanId)
                ->orderBy('installment_number')
                ->get();

            foreach ($schedules as $schedule) {
                $this->refreshScheduleRow($schedule, $orgId);
            }

            // ── 2. Recompute loan-level status ────────────────────────────
            $this->refreshLoanStatus($loanId, $orgId);

            // ── 3. Stamp borrower's last repayment timestamp ──────────────
            if ($payment->borrower_id) {
                Borrower::where('organisation_id', $orgId)
                    ->where('id', $payment->borrower_id)
                    ->update(['last_repayment_at' => now()]);
            }
        });
    }

    /**
     * Mark overdue schedules on a loan based on today's date.
     *
     * Called by the SweepOverdueLoans command (nightly).
     * Penalty accrual and DelinquencyRecord creation are handled by the
     * command itself before calling this method, which refreshes the
     * loan-level status after rows have been marked.
     */
    public function markOverdueSchedules(int $loanId, int $orgId): void
    {
        DB::transaction(function () use ($loanId, $orgId) {
            $today = now()->toDateString();

            LoanSchedule::where('organisation_id', $orgId)
                ->where('loan_id', $loanId)
                ->whereIn('status', [self::STATUS_PENDING, self::STATUS_PARTIAL])
                ->where('due_date', '<', $today)
                ->update(['status' => self::STATUS_OVERDUE]);

            $this->refreshLoanStatus($loanId, $orgId);
        });
    }

    // ── Internal refreshers ───────────────────────────────────────────────

    /**
     * Sum all payment_allocations for a schedule row and update its
     * paid columns + status.
     */
    private function refreshScheduleRow(LoanSchedule $schedule, int $orgId): void
    {
        // Aggregate allocations for this schedule row
        $sums = DB::table('payment_allocations')
            ->join('loan_payments', function ($join) use ($orgId) {
                $join->on('loan_payments.id', '=', 'payment_allocations.loan_payment_id')
                     ->where('loan_payments.organisation_id', '=', $orgId)
                     ->where('loan_payments.status', '=', 'posted');  // exclude reversed payments
            })
            ->where('payment_allocations.organisation_id', $orgId)
            ->where('payment_allocations.loan_schedule_id', $schedule->id)
            ->selectRaw("
                COALESCE(SUM(CASE WHEN payment_allocations.allocation_type = 'principal' THEN payment_allocations.amount_cents ELSE 0 END), 0) AS principal_paid,
                COALESCE(SUM(CASE WHEN payment_allocations.allocation_type = 'interest'  THEN payment_allocations.amount_cents ELSE 0 END), 0) AS interest_paid,
                COALESCE(SUM(CASE WHEN payment_allocations.allocation_type = 'fees'      THEN payment_allocations.amount_cents ELSE 0 END), 0) AS fees_paid,
                COALESCE(SUM(CASE WHEN payment_allocations.allocation_type = 'penalty'   THEN payment_allocations.amount_cents ELSE 0 END), 0) AS penalty_paid
            ")
            ->first();

        $principalPaid = (int) ($sums->principal_paid ?? 0);
        $interestPaid  = (int) ($sums->interest_paid  ?? 0);
        $feesPaid      = (int) ($sums->fees_paid      ?? 0);
        $penaltyPaid   = (int) ($sums->penalty_paid   ?? 0);
        $totalPaid     = $principalPaid + $interestPaid + $feesPaid + $penaltyPaid;

        $totalDue = (int) $schedule->total_due_cents
            + (int) $schedule->penalty_due_cents;  // include any accrued penalty

        $status    = $this->deriveScheduleStatus($schedule, $totalPaid, $totalDue);
        $paidAt    = ($status === self::STATUS_PAID) ? ($schedule->paid_at ?? now()) : null;

        $schedule->update([
            'principal_paid_cents' => $principalPaid,
            'interest_paid_cents'  => $interestPaid,
            'fees_paid_cents'      => $feesPaid,
            'penalty_paid_cents'   => $penaltyPaid,
            'total_paid_cents'     => $totalPaid,
            'status'               => $status,
            'paid_at'              => $paidAt,
        ]);
    }

    /**
     * Derive a schedule row's status from its payment state.
     *
     * Logic:
     *  - If total_paid >= total_due   → paid
     *  - If total_paid > 0            → partial  (or overdue+partial if past due)
     *  - If past due date             → overdue
     *  - Otherwise                    → pending
     */
    private function deriveScheduleStatus(LoanSchedule $schedule, int $totalPaid, int $totalDue): string
    {
        if ($totalPaid >= $totalDue) {
            return self::STATUS_PAID;
        }

        $isPastDue = now()->startOfDay()->gt($schedule->due_date);

        if ($totalPaid > 0) {
            // Partially paid — still show overdue context if past due date
            return self::STATUS_PARTIAL;
        }

        return $isPastDue ? self::STATUS_OVERDUE : self::STATUS_PENDING;
    }

    /**
     * Recompute the loan's status from the aggregate of its schedule rows.
     *
     * Loan status rules:
     *  completed         — all schedules are 'paid'
     *  overdue           — at least one schedule is 'overdue' (and not all paid)
     *  active            — at least one schedule is 'partial' or 'pending', none overdue
     *  (no change)       — if the loan is already defaulted/written_off/cancelled
     */
    private function refreshLoanStatus(int $loanId, int $orgId): void
    {
        $loan = Loan::where('organisation_id', $orgId)->find($loanId);

        if (!$loan) {
            return;
        }

        // Don't override terminal statuses
        if (in_array($loan->status, ['defaulted', 'written_off', 'cancelled'], true)) {
            return;
        }

        $statuses = LoanSchedule::where('organisation_id', $orgId)
            ->where('loan_id', $loanId)
            ->pluck('status')
            ->toArray();

        if (empty($statuses)) {
            return;
        }

        $uniqueStatuses = array_unique($statuses);

        if (count($uniqueStatuses) === 1 && $uniqueStatuses[0] === self::STATUS_PAID) {
            // Every instalment is fully paid
            $loan->update([
                'status'       => 'completed',
                'completed_at' => now(),
            ]);
            return;
        }

        if (in_array(self::STATUS_OVERDUE, $statuses, true)) {
            $loan->update(['status' => 'overdue']);
            return;
        }

        // At least one pending/partial and none overdue → active
        if (in_array($loan->status, ['disbursement_pending', 'approved'], false)) {
            // Don't push back from active to disbursement_pending
            return;
        }

        $loan->update(['status' => 'active']);
    }
}
