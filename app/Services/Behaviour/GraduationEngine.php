<?php

namespace App\Services\Behaviour;

use App\Models\Borrower;
use App\Models\BorrowerTier;
use App\Models\DefaultRecord;
use App\Models\GraduationEvent;
use App\Models\Loan;
use App\Models\TierHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * GraduationEngine
 *
 * Evaluates whether a borrower should be promoted to the next tier after
 * a loan is completed, and executes the tier change if warranted.
 *
 * Evaluation flow
 * ────────────────
 * 1. Load the borrower's current tier.
 * 2. Find the next tier up (by tier_number + 1) for this organisation.
 * 3. Check whether the borrower meets ALL of the next tier's entry thresholds:
 *      - minimum_successful_loans
 *      - minimum_on_time_loans
 *      - minimum_consecutive_on_time_loans
 *      - minimum_behaviour_score
 *      - minimum_financial_score (if set, checked against current financial profile)
 * 4. If all criteria are met, promote:
 *      - Update borrowers.current_tier_id
 *      - Insert tier_histories row
 *      - Insert graduation_events row
 *      - Stamp borrowers.last_graduated_at
 *
 * Demotion is not handled here — that belongs to a separate delinquency/default
 * workflow that would call demoteBorrower().
 *
 * This engine is intentionally conservative: it only promotes one tier at a
 * time (the next adjacent tier), even if the borrower technically meets the
 * criteria for a tier two steps up. Re-run after each loan to allow multi-step
 * catch-ups over time.
 */
class GraduationEngine
{
    // ── Public API ────────────────────────────────────────────────────────

    /**
     * Evaluate graduation for a borrower after a loan has completed.
     *
     * Returns true if the borrower was promoted, false otherwise.
     */
    public function evaluate(Borrower $borrower, Loan $triggeringLoan): bool
    {
        $orgId = (int) $borrower->organisation_id;

        $currentTier = $borrower->currentTier;

        // ── Find the next tier ────────────────────────────────────────────
        $nextTier = $this->findNextTier($orgId, $currentTier?->tier_number ?? 0);

        if (!$nextTier) {
            Log::info('GraduationEngine: no higher tier available for borrower', [
                'borrower_id'       => $borrower->id,
                'current_tier'      => $currentTier?->name ?? 'none',
                'organisation_id'   => $orgId,
            ]);
            return false;
        }

        // ── Load current financial score ──────────────────────────────────
        $financialScore = $this->currentFinancialScore($borrower->id, $orgId);

        // ── Check all criteria ────────────────────────────────────────────
        $reasons = $this->checkCriteria($borrower, $nextTier, $financialScore);

        if (!empty($reasons)) {
            Log::info('GraduationEngine: borrower does not qualify for graduation', [
                'borrower_id'   => $borrower->id,
                'next_tier'     => $nextTier->name,
                'reasons'       => $reasons,
            ]);
            return false;
        }

        // ── Execute graduation ────────────────────────────────────────────
        $this->promote($borrower, $currentTier, $nextTier, $triggeringLoan, $financialScore);

        Log::info('GraduationEngine: borrower graduated', [
            'borrower_id'   => $borrower->id,
            'from_tier'     => $currentTier?->name ?? 'none',
            'to_tier'       => $nextTier->name,
        ]);

        return true;
    }

    /**
     * Demote a borrower by one tier (called on default/written-off event).
     *
     * Will not demote below the entry-tier.
     */
    public function demote(Borrower $borrower, Loan $triggeringLoan, string $reason = ''): bool
    {
        $orgId       = (int) $borrower->organisation_id;
        $currentTier = $borrower->currentTier;

        if (!$currentTier) {
            return false;
        }

        // Find the tier one step below
        $lowerTier = BorrowerTier::where('organisation_id', $orgId)
            ->where('tier_number', $currentTier->tier_number - 1)
            ->where('is_active', true)
            ->first();

        // Don't demote below the lowest available tier
        if (!$lowerTier) {
            Log::info('GraduationEngine: borrower is already at the entry tier, demotion skipped', [
                'borrower_id' => $borrower->id,
            ]);
            return false;
        }

        $financialScore = $this->currentFinancialScore($borrower->id, $orgId);

        DB::transaction(function () use (
            $borrower, $currentTier, $lowerTier,
            $triggeringLoan, $financialScore, $reason, $orgId
        ) {
            // Update borrower's tier
            $borrower->update(['current_tier_id' => $lowerTier->id]);

            $snapshot = $this->buildSnapshot($borrower, $financialScore);

            // Record tier history
            TierHistory::create([
                'organisation_id'           => $orgId,
                'borrower_id'               => $borrower->id,
                'from_tier_id'              => $currentTier->id,
                'to_tier_id'                => $lowerTier->id,
                'change_type'               => 'demotion',
                'reason'                    => $reason ?: 'Loan default',
                'behaviour_score'           => $borrower->behaviour_score,
                'financial_score'           => $financialScore,
                'successful_loans_count'    => $borrower->successful_loans_count,
                'on_time_loans_count'       => $borrower->on_time_loans_count,
                'consecutive_on_time_loans' => $borrower->consecutive_on_time_loans,
                'rule_version'              => 'graduation-engine-v1',
                'decision_snapshot'         => $snapshot,
                'effective_at'              => now(),
            ]);

            // Record graduation event (type = demoted)
            GraduationEvent::create([
                'organisation_id'  => $orgId,
                'borrower_id'      => $borrower->id,
                'from_tier_id'     => $currentTier->id,
                'to_tier_id'       => $lowerTier->id,
                'loan_id'          => $triggeringLoan->id,
                'event_type'       => 'demoted',
                'behaviour_score'  => $borrower->behaviour_score,
                'financial_score'  => $financialScore,
                'reason'           => $reason ?: 'Loan default triggered demotion',
                'decision_snapshot'=> $snapshot,
                'occurred_at'      => now(),
            ]);

            // Create a DefaultRecord so the default history is visible in the
            // borrower profile and risk reporting.
            $alreadyDefaulted = DefaultRecord::where('organisation_id', $orgId)
                ->where('loan_id', $triggeringLoan->id)
                ->exists();

            if (!$alreadyDefaulted) {
                // Aggregate outstanding amounts from the schedule
                $outstanding = DB::table('loan_schedules')
                    ->where('organisation_id', $orgId)
                    ->where('loan_id', $triggeringLoan->id)
                    ->selectRaw('
                        COALESCE(SUM(GREATEST(principal_due_cents  - principal_paid_cents,  0)), 0) as principal_out,
                        COALESCE(SUM(GREATEST(interest_due_cents   - interest_paid_cents,   0)), 0) as interest_out,
                        COALESCE(SUM(GREATEST(fees_due_cents       - fees_paid_cents,       0)), 0) as fees_out,
                        COALESCE(SUM(GREATEST(penalty_due_cents    - penalty_paid_cents,    0)), 0) as penalty_out
                    ')
                    ->first();

                $totalOut = ($outstanding->principal_out ?? 0)
                          + ($outstanding->interest_out  ?? 0)
                          + ($outstanding->fees_out      ?? 0)
                          + ($outstanding->penalty_out   ?? 0);

                $daysOverdue = (int) DB::table('loan_schedules')
                    ->where('organisation_id', $orgId)
                    ->where('loan_id', $triggeringLoan->id)
                    ->where('status', 'overdue')
                    ->selectRaw('MAX(DATEDIFF(CURDATE(), due_date)) as max_days')
                    ->value('max_days');

                DefaultRecord::create([
                    'organisation_id'             => $orgId,
                    'borrower_id'                 => $borrower->id,
                    'loan_id'                     => $triggeringLoan->id,
                    'reason'                      => $reason ?: 'Loan default',
                    'defaulted_at'                => now()->toDateString(),
                    'days_overdue'                => $daysOverdue,
                    'principal_outstanding_cents' => (int) ($outstanding->principal_out ?? 0),
                    'interest_outstanding_cents'  => (int) ($outstanding->interest_out  ?? 0),
                    'fees_outstanding_cents'      => (int) ($outstanding->fees_out      ?? 0),
                    'penalty_outstanding_cents'   => (int) ($outstanding->penalty_out   ?? 0),
                    'total_outstanding_cents'     => $totalOut,
                    'status'                      => 'open',
                    'default_snapshot'            => $snapshot,
                ]);
            }
        });

        return true;
    }

    // ── Private helpers ───────────────────────────────────────────────────

    /**
     * Find the tier directly above the current tier_number.
     */
    private function findNextTier(int $orgId, int $currentTierNumber): ?BorrowerTier
    {
        return BorrowerTier::where('organisation_id', $orgId)
            ->where('tier_number', $currentTierNumber + 1)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Check all entry criteria for the target tier.
     * Returns an array of human-readable failure reasons (empty = all passed).
     *
     * @return string[]
     */
    private function checkCriteria(
        Borrower     $borrower,
        BorrowerTier $targetTier,
        ?float       $financialScore,
    ): array {
        $reasons = [];

        if ($borrower->successful_loans_count < (int) $targetTier->minimum_successful_loans) {
            $reasons[] = sprintf(
                'Needs %d successful loans, has %d.',
                $targetTier->minimum_successful_loans,
                $borrower->successful_loans_count
            );
        }

        if ($borrower->on_time_loans_count < (int) $targetTier->minimum_on_time_loans) {
            $reasons[] = sprintf(
                'Needs %d on-time loans, has %d.',
                $targetTier->minimum_on_time_loans,
                $borrower->on_time_loans_count
            );
        }

        if ($borrower->consecutive_on_time_loans < (int) $targetTier->minimum_consecutive_on_time_loans) {
            $reasons[] = sprintf(
                'Needs %d consecutive on-time loans, has %d.',
                $targetTier->minimum_consecutive_on_time_loans,
                $borrower->consecutive_on_time_loans
            );
        }

        if ((float) $targetTier->minimum_behaviour_score > 0) {
            if ((float) $borrower->behaviour_score < (float) $targetTier->minimum_behaviour_score) {
                $reasons[] = sprintf(
                    'Behaviour score %.4f is below required %.4f.',
                    $borrower->behaviour_score,
                    $targetTier->minimum_behaviour_score
                );
            }
        }

        if ($targetTier->minimum_financial_score !== null
            && (float) $targetTier->minimum_financial_score > 0) {
            if ($financialScore === null) {
                $reasons[] = 'A verified financial score is required for this tier.';
            } elseif ($financialScore < (float) $targetTier->minimum_financial_score) {
                $reasons[] = sprintf(
                    'Financial score %.2f%% is below required %.2f%%.',
                    $financialScore * 100,
                    (float) $targetTier->minimum_financial_score * 100
                );
            }
        }

        return $reasons;
    }

    /**
     * Execute the tier promotion within a DB transaction.
     */
    private function promote(
        Borrower     $borrower,
        ?BorrowerTier $fromTier,
        BorrowerTier  $toTier,
        Loan          $triggeringLoan,
        ?float        $financialScore,
    ): void {
        $orgId = (int) $borrower->organisation_id;

        DB::transaction(function () use (
            $borrower, $fromTier, $toTier,
            $triggeringLoan, $financialScore, $orgId
        ) {
            // Update the borrower's tier
            $borrower->update([
                'current_tier_id'   => $toTier->id,
                'last_graduated_at' => now(),
            ]);

            $snapshot = $this->buildSnapshot($borrower, $financialScore);

            // Write the tier history row
            TierHistory::create([
                'organisation_id'           => $orgId,
                'borrower_id'               => $borrower->id,
                'from_tier_id'              => $fromTier?->id,
                'to_tier_id'                => $toTier->id,
                'change_type'               => $fromTier ? 'graduation' : 'initial',
                'reason'                    => "Graduated to {$toTier->name} after completing loan #{$triggeringLoan->id}.",
                'behaviour_score'           => $borrower->behaviour_score,
                'financial_score'           => $financialScore,
                'successful_loans_count'    => $borrower->successful_loans_count,
                'on_time_loans_count'       => $borrower->on_time_loans_count,
                'consecutive_on_time_loans' => $borrower->consecutive_on_time_loans,
                'rule_version'              => 'graduation-engine-v1',
                'decision_snapshot'         => $snapshot,
                'effective_at'              => now(),
            ]);

            // Write the graduation event row
            GraduationEvent::create([
                'organisation_id'  => $orgId,
                'borrower_id'      => $borrower->id,
                'from_tier_id'     => $fromTier?->id,
                'to_tier_id'       => $toTier->id,
                'loan_id'          => $triggeringLoan->id,
                'event_type'       => 'graduated',
                'behaviour_score'  => $borrower->behaviour_score,
                'financial_score'  => $financialScore,
                'reason'           => "Graduated to {$toTier->name} after loan #{$triggeringLoan->id} completion.",
                'decision_snapshot'=> $snapshot,
                'occurred_at'      => now(),
            ]);
        });
    }

    /**
     * Fetch the current financial score from the financial_profiles table.
     */
    private function currentFinancialScore(int $borrowerId, int $orgId): ?float
    {
        $fp = DB::table('financial_profiles')
            ->where('organisation_id', $orgId)
            ->where('borrower_id', $borrowerId)
            ->where('is_current', true)
            ->value('financial_score');

        return $fp !== null ? (float) $fp : null;
    }

    /**
     * Build a decision snapshot array for audit purposes.
     */
    private function buildSnapshot(Borrower $borrower, ?float $financialScore): array
    {
        return [
            'engine_version'            => 'graduation-engine-v1',
            'evaluated_at'              => now()->toIso8601String(),
            'behaviour_score'           => $borrower->behaviour_score,
            'financial_score'           => $financialScore,
            'successful_loans_count'    => $borrower->successful_loans_count,
            'on_time_loans_count'       => $borrower->on_time_loans_count,
            'consecutive_on_time_loans' => $borrower->consecutive_on_time_loans,
            'late_loans_count'          => $borrower->late_loans_count,
            'defaulted_loans_count'     => $borrower->defaulted_loans_count,
        ];
    }
}
