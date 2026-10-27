<?php

namespace App\Services\Behaviour;

use App\Models\BehaviourEvent;
use App\Models\BehaviourScore;
use App\Models\Borrower;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\LoanSchedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * BehaviourScoreService
 *
 * Records behaviour events and maintains the borrower's behaviour score.
 *
 * Design philosophy
 * ─────────────────
 * Each meaningful payment or loan lifecycle event is recorded as an immutable
 * behaviour_event row with an idempotency_key.  The score is then re-derived
 * from the full history of events via a weighted formula, rather than being
 * a running total that can drift.  The result is snapshotted into:
 *   • behaviour_scores  (historical record, one row per recalculation)
 *   • borrowers.behaviour_score  (denormalised cache for fast appraisal reads)
 *   • borrowers.*_loans_count   (denormalised counters)
 *
 * Event types + score impacts
 * ───────────────────────────
 *   loan_disbursed           0.00  (informational only)
 *   payment_made_on_time    +0.10  (paid within grace period on or before due date)
 *   payment_made_early      +0.15  (paid > 1 day before due date)
 *   payment_made_late       -0.08  (paid after due date, before default)
 *   partial_payment         -0.03  (partial payment on due date — better than nothing)
 *   missed_installment      -0.15  (no payment at all on due date)
 *   loan_completed          +0.20  (all instalments cleared)
 *   loan_defaulted          -0.40  (loan moved to defaulted status)
 *   loan_recovered          +0.10  (defaulted loan subsequently repaid)
 *
 * The final score is clamped to [0.00, 1.00].
 * New borrowers start at 0.00 and build score through repayment behaviour.
 *
 * Score model version: 'behaviour-v1'
 */
class BehaviourScoreService
{
    private const MODEL_VERSION = 'behaviour-v1';

    /** Score impact table. Positive = reward, negative = penalty. */
    private const IMPACTS = [
        'loan_disbursed'         =>  0.00,
        'payment_made_on_time'   =>  0.10,
        'payment_made_early'     =>  0.15,
        'payment_made_late'      => -0.08,
        'partial_payment'        => -0.03,
        'missed_installment'     => -0.15,
        'loan_completed'         =>  0.20,
        'loan_defaulted'         => -0.40,
        'loan_recovered'         =>  0.10,
    ];

    // ── Public event recorders ────────────────────────────────────────────

    /**
     * Record that a loan was disbursed (informational — no score impact).
     */
    public function recordDisbursed(Loan $loan): void
    {
        $this->record(
            borrowerId:   (int) $loan->borrower_id,
            orgId:        (int) $loan->organisation_id,
            eventType:    'loan_disbursed',
            loanId:       $loan->id,
            idempotencyKey: "disbursed-{$loan->organisation_id}-{$loan->id}",
            eventData:    ['loan_id' => $loan->id, 'principal_cents' => $loan->principal_cents],
        );

        // Increment lifetime loans counter
        Borrower::where('organisation_id', $loan->organisation_id)
            ->where('id', $loan->borrower_id)
            ->update([
                'lifetime_loans_count' => DB::raw('lifetime_loans_count + 1'),
                'last_loan_at'         => now(),
            ]);
    }

    /**
     * Record the result of a payment against a specific schedule instalment.
     *
     * Evaluates whether the payment was early, on-time, late, or partial.
     */
    public function recordPaymentEvent(
        LoanPayment  $payment,
        LoanSchedule $schedule,
    ): void {
        $orgId      = (int) $payment->organisation_id;
        $borrowerId = (int) $payment->borrower_id;
        $loanId     = (int) $payment->loan_id;

        // Determine whether this instalment is now fully paid
        $scheduleFullyPaid = ((int) $schedule->total_paid_cents >= (int) $schedule->total_due_cents);

        // Days relative to due date (negative = early, positive = late)
        $dueDate   = \Carbon\Carbon::parse($schedule->due_date)->startOfDay();
        $payDate   = \Carbon\Carbon::parse($payment->received_at ?? now())->startOfDay();
        $daysLate  = $dueDate->diffInDays($payDate, false); // signed

        if (!$scheduleFullyPaid) {
            $eventType = 'partial_payment';
        } elseif ($daysLate < -1) {
            $eventType = 'payment_made_early';
        } elseif ($daysLate <= 0) {
            $eventType = 'payment_made_on_time';
        } else {
            $eventType = 'payment_made_late';
        }

        $key = "payment-{$orgId}-{$payment->id}-schedule-{$schedule->id}";

        $this->record(
            borrowerId:     $borrowerId,
            orgId:          $orgId,
            eventType:      $eventType,
            loanId:         $loanId,
            scheduleId:     $schedule->id,
            paymentId:      $payment->id,
            idempotencyKey: $key,
            eventData: [
                'payment_id'       => $payment->id,
                'schedule_id'      => $schedule->id,
                'installment_num'  => $schedule->installment_number,
                'due_date'         => $schedule->due_date,
                'paid_at'          => $payment->received_at,
                'days_late'        => $daysLate,
                'amount_cents'     => $payment->amount_cents,
                'fully_paid'       => $scheduleFullyPaid,
            ],
        );

        $this->recalculateScore($borrowerId, $orgId);
    }

    /**
     * Record that all instalments on a loan have been fully cleared.
     */
    public function recordLoanCompleted(Loan $loan): void
    {
        $orgId      = (int) $loan->organisation_id;
        $borrowerId = (int) $loan->borrower_id;

        $this->record(
            borrowerId:     $borrowerId,
            orgId:          $orgId,
            eventType:      'loan_completed',
            loanId:         $loan->id,
            idempotencyKey: "completed-{$orgId}-{$loan->id}",
            eventData: [
                'loan_id'            => $loan->id,
                'total_payable_cents'=> $loan->total_payable_cents,
                'completed_at'       => now()->toIso8601String(),
            ],
        );

        // Update denormalised counters
        DB::transaction(function () use ($loan, $orgId, $borrowerId) {
            $borrower = Borrower::where('organisation_id', $orgId)
                ->lockForUpdate()
                ->findOrFail($borrowerId);

            // Determine if the loan was repaid on time (all schedules paid by due date)
            $wasOnTime = $this->loanWasPaidOnTime($loan->id, $orgId);
            $daysEarly = $this->daysCompletedEarly($loan, $orgId);
            $wasEarly  = $daysEarly > 1;

            $borrower->increment('successful_loans_count');

            if ($wasOnTime || $wasEarly) {
                $borrower->increment('on_time_loans_count');
                $borrower->increment('consecutive_on_time_loans');
            } else {
                // Reset consecutive streak on a late completion
                $borrower->update(['consecutive_on_time_loans' => 0]);
            }
        });

        $this->recalculateScore($borrowerId, $orgId);
    }

    /**
     * Record that a loan has defaulted.
     */
    public function recordLoanDefaulted(Loan $loan): void
    {
        $orgId      = (int) $loan->organisation_id;
        $borrowerId = (int) $loan->borrower_id;

        $this->record(
            borrowerId:     $borrowerId,
            orgId:          $orgId,
            eventType:      'loan_defaulted',
            loanId:         $loan->id,
            idempotencyKey: "defaulted-{$orgId}-{$loan->id}",
            eventData: [
                'loan_id'    => $loan->id,
                'defaulted_at'=> now()->toIso8601String(),
            ],
        );

        Borrower::where('organisation_id', $orgId)
            ->where('id', $borrowerId)
            ->update([
                'defaulted_loans_count'     => DB::raw('defaulted_loans_count + 1'),
                'consecutive_on_time_loans' => 0,    // reset streak
            ]);

        $this->recalculateScore($borrowerId, $orgId);
    }

    /**
     * Record that a defaulted loan has been subsequently recovered.
     */
    public function recordLoanRecovered(Loan $loan): void
    {
        $orgId      = (int) $loan->organisation_id;
        $borrowerId = (int) $loan->borrower_id;

        $this->record(
            borrowerId:     $borrowerId,
            orgId:          $orgId,
            eventType:      'loan_recovered',
            loanId:         $loan->id,
            idempotencyKey: "recovered-{$orgId}-{$loan->id}",
            eventData: ['loan_id' => $loan->id],
        );

        $this->recalculateScore($borrowerId, $orgId);
    }

    /**
     * Record a missed instalment (called by the nightly overdue sweeper).
     */
    public function recordMissedInstalment(LoanSchedule $schedule): void
    {
        $orgId      = (int) $schedule->organisation_id;
        $loanId     = (int) $schedule->loan_id;

        $loan = Loan::where('organisation_id', $orgId)->find($loanId);
        if (!$loan) {
            return;
        }

        $borrowerId = (int) $loan->borrower_id;

        $key = "missed-{$orgId}-schedule-{$schedule->id}";

        $this->record(
            borrowerId:     $borrowerId,
            orgId:          $orgId,
            eventType:      'missed_installment',
            loanId:         $loanId,
            scheduleId:     $schedule->id,
            idempotencyKey: $key,
            eventData: [
                'schedule_id'     => $schedule->id,
                'installment_num' => $schedule->installment_number,
                'due_date'        => $schedule->due_date,
                'amount_due'      => $schedule->total_due_cents,
            ],
        );

        Borrower::where('organisation_id', $orgId)
            ->where('id', $borrowerId)
            ->update([
                'late_loans_count'          => DB::raw('late_loans_count + 1'),
                'consecutive_on_time_loans' => 0,
            ]);

        $this->recalculateScore($borrowerId, $orgId);
    }

    // ── Score recalculation ───────────────────────────────────────────────

    /**
     * Recalculate the borrower's behaviour score from the full event history.
     *
     * The score is the clamped sum of all event impacts, weighted by recency
     * (events older than 12 months count at 50%).
     */
    public function recalculateScore(int $borrowerId, int $orgId): float
    {
        $events = BehaviourEvent::where('organisation_id', $orgId)
            ->where('borrower_id', $borrowerId)
            ->orderBy('occurred_at')
            ->get(['event_type', 'score_impact', 'occurred_at']);

        $now         = now();
        $cutoff      = $now->copy()->subMonths(12);
        $rawScore    = 0.0;
        $observations = 0;

        foreach ($events as $event) {
            $impact = (float) $event->score_impact;
            if ($impact == 0.0) {
                continue;
            }

            // Recency weight: events older than 12 months count at half
            $weight = \Carbon\Carbon::parse($event->occurred_at)->lt($cutoff) ? 0.5 : 1.0;
            $rawScore += $impact * $weight;
            $observations++;
        }

        // Clamp to [0, 1]
        $score = round(min(1.0, max(0.0, $rawScore)), 4);

        $snapshot = [
            'model_version'    => self::MODEL_VERSION,
            'observation_count'=> $observations,
            'raw_sum'          => $rawScore,
            'clamped_score'    => $score,
            'calculated_at'    => $now->toIso8601String(),
        ];

        DB::transaction(function () use ($borrowerId, $orgId, $score, $observations, $snapshot) {
            // Insert a historical score record
            BehaviourScore::create([
                'organisation_id'  => $orgId,
                'borrower_id'      => $borrowerId,
                'score'            => $score,
                'model_version'    => self::MODEL_VERSION,
                'observation_count'=> $observations,
                'score_snapshot'   => $snapshot,
                'calculated_at'    => now(),
            ]);

            // Update the denormalised cache on the borrower
            Borrower::where('organisation_id', $orgId)
                ->where('id', $borrowerId)
                ->update(['behaviour_score' => $score]);
        });

        return $score;
    }

    // ── Internal helpers ──────────────────────────────────────────────────

    /**
     * Insert a BehaviourEvent.  Idempotent on idempotency_key.
     */
    private function record(
        int    $borrowerId,
        int    $orgId,
        string $eventType,
        int    $loanId,
        string $idempotencyKey,
        array  $eventData = [],
        ?int   $scheduleId = null,
        ?int   $paymentId  = null,
    ): void {
        // Skip if already recorded
        if (BehaviourEvent::where('organisation_id', $orgId)
                ->where('idempotency_key', $idempotencyKey)
                ->exists()) {
            return;
        }

        $impact = (float) (self::IMPACTS[$eventType] ?? 0.0);

        BehaviourEvent::create([
            'organisation_id' => $orgId,
            'borrower_id'     => $borrowerId,
            'loan_id'         => $loanId,
            'loan_schedule_id'=> $scheduleId,
            'loan_payment_id' => $paymentId,
            'event_type'      => $eventType,
            'score_impact'    => $impact,
            'occurred_at'     => now(),
            'idempotency_key' => $idempotencyKey,
            'event_data'      => $eventData,
        ]);
    }

    /**
     * Check whether all overdue schedules for a loan were paid on or before
     * their due date (i.e. no late payments occurred).
     */
    private function loanWasPaidOnTime(int $loanId, int $orgId): bool
    {
        // If any behaviour event of type 'payment_made_late' or
        // 'missed_installment' exists for this loan, it was not on time.
        return !BehaviourEvent::where('organisation_id', $orgId)
            ->where('loan_id', $loanId)
            ->whereIn('event_type', ['payment_made_late', 'missed_installment'])
            ->exists();
    }

    /**
     * Return how many days before the maturity date the loan was completed.
     * Negative value means it was completed after maturity.
     */
    private function daysCompletedEarly(Loan $loan, int $orgId): int
    {
        if (empty($loan->matures_at) || empty($loan->completed_at)) {
            return 0;
        }

        $maturity   = \Carbon\Carbon::parse($loan->matures_at)->startOfDay();
        $completed  = \Carbon\Carbon::parse($loan->completed_at)->startOfDay();

        return (int) $completed->diffInDays($maturity, false);
    }
}
