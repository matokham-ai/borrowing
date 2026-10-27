<?php

namespace App\Jobs;

use App\Models\Loan;
use App\Models\MpesaTransaction;
use App\Services\Behaviour\BehaviourScoreService;
use App\Services\Behaviour\GraduationEngine;
use App\Services\Mpesa\MpesaDisbursementService;
use App\Services\Mpesa\MpesaPaymentMatcher;
use App\Services\Payments\LoanBalanceService;
use App\Services\Payments\PaymentAllocationService;
use App\Services\Payments\PaymentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ProcessMpesaTransaction
 *
 * The core background worker that orchestrates the full payment lifecycle
 * for a single MpesaTransaction after the callback has been ingested.
 *
 * Inbound repayment flow (C2B / STK, direction = inbound):
 * ─────────────────────────────────────────────────────────
 *  1. Load the mpesa_transaction.
 *  2. Skip failed/timeout transactions; retry success or unmatched-received transactions.
 *  3. Run MpesaPaymentMatcher to find the borrower + loan.
 *  4. If unmatched → dispatch ReconcileMpesaTransaction; stop here.
 *  5. Create a LoanPayment via PaymentService.
 *  6. Allocate via PaymentAllocationService (waterfall).
 *  7. Refresh schedule + loan status via LoanBalanceService.
 *  8. Record behaviour event via BehaviourScoreService.
 *  9. If loan just completed → run GraduationEngine.
 *
 * Outbound disbursement flow (B2C, direction = outbound):
 * ─────────────────────────────────────────────────────────
 *  1. Load the mpesa_transaction.
 *  2. Route to MpesaDisbursementService.handleDisbursementSuccess/Failure.
 *
 * Reversal flow:
 * ──────────────
 *  Routed to ProcessMpesaReversal job.
 *
 * Uses the default queue consumed by the application's running workers.
 * Retried 3 times (10 s / 60 s / 300 s backoff).
 */
class ProcessMpesaTransaction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int   $tries   = 3;
    public int   $timeout = 120;
    public array $backoff  = [10, 60, 300];

    public function __construct(
        public readonly int $mpesaTransactionId,
        public readonly int $organisationId,
    ) {
    }

    public function handle(
        MpesaPaymentMatcher       $matcher,
        PaymentService            $paymentService,
        PaymentAllocationService  $allocationService,
        LoanBalanceService        $balanceService,
        BehaviourScoreService     $behaviourService,
        GraduationEngine          $graduationEngine,
        MpesaDisbursementService  $disbursementService,
    ): void {
        $tx = MpesaTransaction::where('organisation_id', $this->organisationId)
            ->find($this->mpesaTransactionId);

        if (!$tx) {
            Log::warning('ProcessMpesaTransaction: transaction not found', [
                'id' => $this->mpesaTransactionId,
            ]);
            return;
        }

        // ── Route by direction ────────────────────────────────────────────
        if ($tx->direction === 'outbound') {
            $this->processOutbound($tx, $disbursementService);
            return;
        }

        // Inbound: repayment / STK / C2B
        $this->processInbound(
            $tx, $matcher, $paymentService, $allocationService,
            $balanceService, $behaviourService, $graduationEngine,
        );
    }

    // ── Inbound processing ────────────────────────────────────────────────

    private function processInbound(
        MpesaTransaction         $tx,
        MpesaPaymentMatcher      $matcher,
        PaymentService           $paymentService,
        PaymentAllocationService $allocationService,
        LoanBalanceService       $balanceService,
        BehaviourScoreService    $behaviourService,
        GraduationEngine         $graduationEngine,
    ): void {
        // ── 1. Only process successful transactions ───────────────────────
        if (!in_array($tx->status, ['success', 'received'], true)) {
            Log::info('ProcessMpesaTransaction: skipping non-success inbound tx', [
                'id'     => $tx->id,
                'status' => $tx->status,
            ]);
            return;
        }

        // ── 2. Match borrower + loan ──────────────────────────────────────
        $matchResult = $matcher->match($tx);
        $matcher->applyMatch($tx, $matchResult);

        if (!$matchResult->matched) {
            $paymentService->createUnmatchedFromTransaction($tx);
            Log::info('ProcessMpesaTransaction: unmatched — dispatching reconciliation', [
                'id'     => $tx->id,
                'reason' => $matchResult->reason,
            ]);
            ReconcileMpesaTransaction::dispatch($tx->id, $this->organisationId);
            return;
        }

        // ── 3. Create the loan payment ────────────────────────────────────
        try {
            $payment = $paymentService->createFromTransaction($tx);
        } catch (\RuntimeException $e) {
            Log::error('ProcessMpesaTransaction: payment creation failed', [
                'tx_id' => $tx->id,
                'error' => $e->getMessage(),
            ]);
            $this->fail($e);
            return;
        }

        // ── 4. Allocate across schedule instalments ───────────────────────
        $allocationService->allocate($payment);

        // ── 5. Refresh schedule + loan status ─────────────────────────────
        $balanceService->refreshAfterPayment($payment);

        // ── 6. Record behaviour event for each affected schedule ──────────
        $loan = Loan::where('organisation_id', $this->organisationId)
            ->with('schedules')
            ->find($payment->loan_id);

        if ($loan) {
            foreach ($loan->schedules as $schedule) {
                // Only record behaviour for schedules this payment touched
                $touched = $payment->allocations()
                    ->where('loan_schedule_id', $schedule->id)
                    ->exists();

                if ($touched) {
                    $behaviourService->recordPaymentEvent($payment, $schedule);
                }
            }

            // ── 7. Post-completion: graduation evaluation ─────────────────
            if ($loan->fresh()->status === 'completed') {
                $behaviourService->recordLoanCompleted($loan);

                $borrower = $loan->borrower()->with('currentTier')->first();
                if ($borrower) {
                    $graduationEngine->evaluate($borrower, $loan);
                }
            }
        }

        // ── 8. Mark transaction as fully processed ────────────────────────
        $tx->update(['processed_at' => now()]);

        Log::info('ProcessMpesaTransaction: inbound payment fully processed', [
            'tx_id'      => $tx->id,
            'payment_id' => $payment->id,
            'loan_id'    => $payment->loan_id,
        ]);
    }

    // ── Outbound (disbursement) processing ────────────────────────────────

    private function processOutbound(
        MpesaTransaction        $tx,
        MpesaDisbursementService $disbursementService,
    ): void {
        if ($tx->status === 'success') {
            $disbursementService->handleDisbursementSuccess($tx);
        } elseif (in_array($tx->status, ['failed', 'timeout'], true)) {
            $disbursementService->handleDisbursementFailure(
                $tx,
                $tx->result_description ?? 'B2C failed'
            );
        }
        // 'submitted' / 'processing' statuses are intermediate — wait for callback
    }

    public function failed(\Throwable $e): void
    {
        Log::error('ProcessMpesaTransaction: job failed permanently', [
            'tx_id' => $this->mpesaTransactionId,
            'error' => $e->getMessage(),
        ]);
    }
}
