<?php

namespace App\Console\Commands;

use App\Models\Borrower;
use App\Models\Loan;
use App\Models\Notification;
use App\Models\Organisation;
use App\Models\SystemEvent;
use App\Services\Behaviour\BehaviourScoreService;
use App\Services\Behaviour\GraduationEngine;
use App\Services\Payments\LoanBalanceService;
use App\Support\Auditor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SweepOverdueLoans
 *
 * Nightly command that:
 *  1. Marks past-due schedule rows as 'overdue'.
 *  2. Accrues configurable late-payment penalties on newly-overdue rows.
 *  3. Refreshes each affected loan's status.
 *  4. Creates DelinquencyRecord rows for newly-overdue schedules.
 *  5. Records a 'payment_missed' behaviour event per overdue schedule.
 *  6. Detects NPL (90-day threshold), marks loans 'defaulted'.
 *  7. Updates borrower behaviour score via BehaviourScoreService::recordLoanDefaulted().
 *  8. Triggers tier demotion via GraduationEngine::demote().
 *  9. Creates SystemEvent and AuditLog entries.
 * 10. Queues in-app notifications for admin users.
 *
 * Runs on a per-organisation basis so multi-tenant isolation is preserved.
 *
 * Scheduled daily at 00:15 (after midnight) in routes/console.php.
 */
class SweepOverdueLoans extends Command
{
    protected $signature   = 'loans:sweep-overdue
                                {--org= : Restrict sweep to a specific organisation ID}
                                {--dry-run : Print what would change without writing anything}';

    protected $description = 'Mark overdue loan schedules, accrue penalties, detect defaults, and record behaviour events';

    public function __construct(
        private readonly LoanBalanceService    $balanceService,
        private readonly BehaviourScoreService $behaviourService,
        private readonly GraduationEngine      $graduationEngine,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $isDryRun  = (bool) $this->option('dry-run');
        $orgFilter = $this->option('org') ? (int) $this->option('org') : null;

        $orgs = Organisation::when($orgFilter, fn ($q) => $q->where('id', $orgFilter))->get(['id', 'name']);

        if ($orgs->isEmpty()) {
            $this->warn('No organisations found.');
            return self::SUCCESS;
        }

        $totalSchedulesMarked = 0;
        $totalPenaltiesAccrued = 0;

        foreach ($orgs as $org) {
            [$marked, $penalised] = $this->processOrganisation((int) $org->id, $isDryRun);
            $totalSchedulesMarked  += $marked;
            $totalPenaltiesAccrued += $penalised;
            $this->line("  Org {$org->name} (#{$org->id}): {$marked} schedules overdue, {$penalised} penalties accrued");
        }

        $mode = $isDryRun ? ' [DRY RUN]' : '';
        $this->info("loans:sweep-overdue complete{$mode} — {$totalSchedulesMarked} schedules marked, {$totalPenaltiesAccrued} penalties accrued.");

        Log::info('SweepOverdueLoans: completed', [
            'schedules_marked'   => $totalSchedulesMarked,
            'penalties_accrued'  => $totalPenaltiesAccrued,
            'dry_run'            => $isDryRun,
        ]);

        return self::SUCCESS;
    }

    // ── Per-organisation processing ───────────────────────────────────────

    /**
     * @return array{int, int}  [schedules_marked, penalties_accrued]
     */
    private function processOrganisation(int $orgId, bool $isDryRun): array
    {
        $today = now()->toDateString();

        // Find all schedule rows that are now past-due but not yet overdue
        $dueSchedules = DB::table('loan_schedules')
            ->join('loans', function ($j) use ($orgId) {
                $j->on('loans.id', '=', 'loan_schedules.loan_id')
                  ->where('loans.organisation_id', '=', $orgId)
                  ->whereIn('loans.status', ['active', 'overdue']);
            })
            ->join('loan_products', 'loan_products.id', '=', 'loans.loan_product_id')
            ->where('loan_schedules.organisation_id', $orgId)
            ->whereIn('loan_schedules.status', ['pending', 'partial'])
            ->where('loan_schedules.due_date', '<', $today)
            ->select([
                'loan_schedules.id',
                'loan_schedules.loan_id',
                'loan_schedules.due_date',
                'loan_schedules.total_due_cents',
                'loan_schedules.total_paid_cents',
                'loan_schedules.penalty_due_cents',
                'loan_schedules.status',
                'loans.borrower_id',
                'loan_products.fee_rate',
                'loan_products.interest_rate',
                'loan_products.code as product_code',
            ])
            ->get();

        if ($dueSchedules->isEmpty()) {
            return [0, 0];
        }

        $markedCount   = 0;
        $penaltyCount  = 0;

        // Group by loan so we only call markOverdueSchedules + refreshLoanStatus once per loan
        $byLoan = $dueSchedules->groupBy('loan_id');

        foreach ($byLoan as $loanId => $schedules) {
            if ($isDryRun) {
                $markedCount += $schedules->count();
                continue;
            }

            DB::transaction(function () use (
                $orgId, $loanId, $schedules, $today,
                &$markedCount, &$penaltyCount
            ) {
                foreach ($schedules as $schedule) {
                    $daysOverdue = (int) now()->startOfDay()->diffInDays($schedule->due_date, false) * -1;
                    $daysOverdue = max(1, $daysOverdue);

                    $outstandingCents = max(0,
                        (int) $schedule->total_due_cents - (int) $schedule->total_paid_cents
                    );

                    // ── Accrue penalty ──────────────────────────────────────
                    // Daily penalty = outstanding * daily_rate (product fee_rate / 365)
                    // Only if the row doesn't already have penalty_due_cents > 0
                    $newPenaltyCents = 0;
                    if ($outstandingCents > 0 && (float) $schedule->fee_rate > 0) {
                        $dailyRate       = ((float) $schedule->fee_rate) / 365;
                        $newPenaltyCents = (int) round($outstandingCents * $dailyRate * $daysOverdue);

                        if ($newPenaltyCents > 0 && (int) $schedule->penalty_due_cents === 0) {
                            DB::table('loan_schedules')
                                ->where('id', $schedule->id)
                                ->update(['penalty_due_cents' => $newPenaltyCents]);
                            $penaltyCount++;
                        }
                    }

                    // ── Mark overdue ────────────────────────────────────────
                    DB::table('loan_schedules')
                        ->where('id', $schedule->id)
                        ->where('organisation_id', $orgId)
                        ->whereIn('status', ['pending', 'partial'])
                        ->update(['status' => 'overdue']);

                    $markedCount++;

                    // ── Create DelinquencyRecord if not already present ─────
                    $alreadyExists = DB::table('delinquency_records')
                        ->where('organisation_id', $orgId)
                        ->where('loan_schedule_id', $schedule->id)
                        ->exists();

                    if (!$alreadyExists) {
                        DB::table('delinquency_records')->insert([
                            'organisation_id'       => $orgId,
                            'borrower_id'           => $schedule->borrower_id,
                            'loan_id'               => $loanId,
                            'loan_schedule_id'      => $schedule->id,
                            'due_date'              => $schedule->due_date,
                            'delinquent_from'       => $today,
                            'days_overdue'          => $daysOverdue,
                            'amount_due_cents'      => (int) $schedule->total_due_cents,
                            'amount_paid_cents'     => (int) $schedule->total_paid_cents,
                            'amount_outstanding_cents' => $outstandingCents,
                            'status'                => 'open',
                            'record_snapshot'       => json_encode([
                                'penalty_accrued_cents' => $newPenaltyCents,
                                'swept_at'              => now()->toIso8601String(),
                            ]),
                            'created_at'            => now(),
                            'updated_at'            => now(),
                        ]);
                    } else {
                        // Update days_overdue and outstanding on existing record
                        DB::table('delinquency_records')
                            ->where('organisation_id', $orgId)
                            ->where('loan_schedule_id', $schedule->id)
                            ->where('status', 'open')
                            ->update([
                                'days_overdue'             => $daysOverdue,
                                'amount_paid_cents'        => (int) $schedule->total_paid_cents,
                                'amount_outstanding_cents' => $outstandingCents,
                                'updated_at'               => now(),
                            ]);
                    }

                    // ── Behaviour event: payment_missed ─────────────────────
                    // Only fire if this is the first day it flips to overdue
                    // (penalty_due_cents was still 0 before this run)
                    if ((int) $schedule->penalty_due_cents === 0) {
                        $this->recordMissedPaymentBehaviour($orgId, $schedule->borrower_id, $loanId, $schedule->id);
                    }
                }

                // Refresh the loan's own status after marking its schedules
                $this->balanceService->markOverdueSchedules((int) $loanId, $orgId);

                // ── Default detection (NPL 90-day threshold) ────────────────
                $loan = Loan::where('organisation_id', $orgId)->find($loanId);

                // Skip if loan no longer exists or is already in a terminal state
                if (!$loan || in_array($loan->status, ['defaulted', 'written_off', 'cancelled'], true)) {
                    return;
                }

                $maxDaysOverdue = DB::table('loan_schedules')
                    ->where('organisation_id', $orgId)
                    ->where('loan_id', $loanId)
                    ->where('status', 'overdue')
                    ->max(DB::raw('DATEDIFF(CURDATE(), due_date)'));

                if ((int) $maxDaysOverdue >= 90) {
                    $this->triggerDefault($orgId, $loan, (int) $maxDaysOverdue);
                }
            });
        }

        return [$markedCount, $penaltyCount];
    }

    // ── Default detection & processing ───────────────────────────────────

    /**
     * Mark a loan as defaulted, update behaviour score, demote tier,
     * emit a SystemEvent + AuditLog, and queue admin notifications.
     *
     * Wrapped in its own try/catch so a failure here never aborts the sweep.
     */
    private function triggerDefault(int $orgId, Loan $loan, int $daysOverdue): void
    {
        try {
            DB::transaction(function () use ($orgId, $loan, $daysOverdue) {
                // (a) Mark the loan defaulted
                $loan->update([
                    'status'       => 'defaulted',
                    'defaulted_at' => now(),
                ]);

                // (b) Record behaviour event + recalculate score.
                //     recordLoanDefaulted() increments defaulted_loans_count,
                //     resets consecutive_on_time_loans, and calls recalculateScore()
                //     internally — do NOT call recalculateScore() again.
                $this->behaviourService->recordLoanDefaulted($loan);

                // (c) Tier demotion.
                //     GraduationEngine::demote() creates DefaultRecord, TierHistory,
                //     and GraduationEvent in its own nested transaction — do not
                //     create DefaultRecord separately.
                $borrower = Borrower::where('organisation_id', $orgId)
                    ->lockForUpdate()
                    ->find($loan->borrower_id);

                if ($borrower) {
                    $this->graduationEngine->demote(
                        $borrower,
                        $loan,
                        "Loan #{$loan->id} defaulted after {$daysOverdue} days overdue"
                    );
                }

                // (d) SystemEvent — guarded by idempotency_key
                $idempotencyKey = "loan-defaulted-{$orgId}-{$loan->id}";

                if (!SystemEvent::where('idempotency_key', $idempotencyKey)->exists()) {
                    SystemEvent::create([
                        'organisation_id' => $orgId,
                        'event_type'      => 'loan.defaulted',
                        'event_version'   => '1',
                        'source'          => 'SweepOverdueLoans',
                        'aggregate_type'  => Loan::class,
                        'aggregate_id'    => $loan->id,
                        'idempotency_key' => $idempotencyKey,
                        'payload'         => [
                            'loan_id'      => $loan->id,
                            'borrower_id'  => $loan->borrower_id,
                            'days_overdue' => $daysOverdue,
                            'defaulted_at' => now()->toIso8601String(),
                        ],
                        'status'          => 'processed',
                        'attempts'        => 1,
                        'occurred_at'     => now(),
                        'processed_at'    => now(),
                    ]);
                }

                // (e) Audit log — orgId override required: no authenticated user in CLI
                Auditor::log('loan.defaulted', $loan, [
                    'new' => [
                        'status'       => 'defaulted',
                        'defaulted_at' => now()->toIso8601String(),
                    ],
                    'description' => "Loan #{$loan->id} auto-defaulted after {$daysOverdue} days overdue by SweepOverdueLoans.",
                    'context'     => [
                        'days_overdue' => $daysOverdue,
                        'source'       => 'SweepOverdueLoans',
                    ],
                    'orgId' => $orgId,
                ]);

                // (f) Queue in-app notifications for admin users
                $this->queueDefaultNotification($orgId, $loan, $daysOverdue);
            });

            Log::info('SweepOverdueLoans: loan defaulted', [
                'loan_id'     => $loan->id,
                'org_id'      => $orgId,
                'days_overdue'=> $daysOverdue,
            ]);
        } catch (\Throwable $e) {
            Log::error('SweepOverdueLoans: triggerDefault failed', [
                'loan_id' => $loan->id,
                'org_id'  => $orgId,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Create in-app Notification rows for every active admin/super-admin user
     * in the organisation so they see the default event in the UI.
     */
    private function queueDefaultNotification(int $orgId, Loan $loan, int $daysOverdue): void
    {
        try {
            // Eager-load the borrower + their user so we can build a display name
            $loan->load('borrower.user');

            $borrowerName = $loan->borrower?->user?->name
                ?? "Borrower #{$loan->borrower_id}";

            $amountKes = number_format((int) $loan->principal_cents / 100, 2);

            // Target active admin / super-admin users in this org
            $userIds = DB::table('users')
                ->join('role_user', 'role_user.user_id', '=', 'users.id')
                ->join('roles', 'roles.id', '=', 'role_user.role_id')
                ->where('users.organisation_id', $orgId)
                ->where('users.is_active', true)
                ->whereIn('roles.name', ['admin', 'super-admin'])
                ->pluck('users.id')
                ->unique();

            // Fall back to all active org users if no admin roles found
            if ($userIds->isEmpty()) {
                $userIds = DB::table('users')
                    ->where('organisation_id', $orgId)
                    ->where('is_active', true)
                    ->pluck('id');
            }

            foreach ($userIds as $userId) {
                Notification::create([
                    'organisation_id'   => $orgId,
                    'user_id'           => $userId,
                    'borrower_id'       => $loan->borrower_id,
                    'channel'           => 'in_app',
                    'notification_type' => 'loan_defaulted',
                    'title'             => 'Loan Defaulted',
                    'message'           => "Loan #{$loan->id} ({$borrowerName}, KES {$amountKes}) marked defaulted after {$daysOverdue} days overdue.",
                    'data'              => [
                        'loan_id'      => $loan->id,
                        'borrower_id'  => $loan->borrower_id,
                        'days_overdue' => $daysOverdue,
                        'amount_cents' => $loan->principal_cents,
                    ],
                    'status'            => 'sent',
                    'sent_at'           => now(),
                    'reference_type'    => Loan::class,
                    'reference_id'      => $loan->id,
                ]);
            }

            Log::info('SweepOverdueLoans: default notifications sent', [
                'loan_id'         => $loan->id,
                'notified_users'  => $userIds->count(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('SweepOverdueLoans: queueDefaultNotification failed', [
                'loan_id' => $loan->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function recordMissedPaymentBehaviour(
        int $orgId, int $borrowerId, int $loanId, int $scheduleId
    ): void {
        try {
            $schedule = \App\Models\LoanSchedule::where('organisation_id', $orgId)->find($scheduleId);
            if ($schedule) {
                $this->behaviourService->recordMissedInstalment($schedule);
            }
        } catch (\Throwable $e) {
            Log::warning('SweepOverdueLoans: behaviour event failed', [
                'loan_id'     => $loanId,
                'schedule_id' => $scheduleId,
                'error'       => $e->getMessage(),
            ]);
        }
    }
}
