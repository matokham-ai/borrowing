<?php

namespace App\Jobs;

use App\Models\Borrower;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Services\Behaviour\BehaviourScoreService;
use App\Services\Behaviour\GraduationEngine;
use App\Services\Payments\LoanBalanceService;
use App\Services\Payments\PaymentAllocationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * AllocateLoanPayment
 *
 * Standalone allocation job, used when:
 *   a) A manual payment is created by an admin (no M-Pesa flow).
 *   b) A previously unmatched payment is manually reconciled and needs
 *      allocation triggered after the fact.
 *   c) The PaymentAllocationService needs to be re-run after an adjustment
 *      (e.g. penalty accrual).
 *
 * Queued on 'payments'. Retried 3 times.
 */
class AllocateLoanPayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int   $tries   = 3;
    public int   $timeout = 90;
    public array $backoff  = [5, 30, 120];

    public function __construct(
        public readonly int $loanPaymentId,
        public readonly int $organisationId,
    ) {
        $this->onQueue('payments');
    }

    public function handle(
        PaymentAllocationService $allocationService,
        LoanBalanceService       $balanceService,
        BehaviourScoreService    $behaviourService,
        GraduationEngine         $graduationEngine,
    ): void {
        $payment = LoanPayment::where('organisation_id', $this->organisationId)
            ->find($this->loanPaymentId);

        if (!$payment) {
            Log::warning('AllocateLoanPayment: payment not found', ['id' => $this->loanPaymentId]);
            return;
        }

        if ($payment->status !== 'posted') {
            Log::info('AllocateLoanPayment: payment not in posted status, skipping', [
                'id'     => $this->loanPaymentId,
                'status' => $payment->status,
            ]);
            return;
        }

        // Allocate
        $allocationService->allocate($payment);

        // Refresh balances
        $balanceService->refreshAfterPayment($payment);

        // Behaviour events for touched schedules
        $loan = Loan::where('organisation_id', $this->organisationId)
            ->with('schedules')
            ->find($payment->loan_id);

        if ($loan) {
            foreach ($loan->schedules as $schedule) {
                $touched = $payment->allocations()
                    ->where('loan_schedule_id', $schedule->id)
                    ->exists();
                if ($touched) {
                    $behaviourService->recordPaymentEvent($payment, $schedule);
                }
            }

            if ($loan->fresh()->status === 'completed') {
                $behaviourService->recordLoanCompleted($loan);
                $borrower = $loan->borrower()->with('currentTier')->first();
                if ($borrower) {
                    $graduationEngine->evaluate($borrower, $loan);
                }
            }
        }

        Log::info('AllocateLoanPayment: allocation complete', [
            'payment_id' => $payment->id,
            'loan_id'    => $payment->loan_id,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('AllocateLoanPayment: job failed permanently', [
            'payment_id' => $this->loanPaymentId,
            'error'      => $e->getMessage(),
        ]);
    }
}
