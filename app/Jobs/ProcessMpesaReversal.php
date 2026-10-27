<?php

namespace App\Jobs;

use App\Models\MpesaTransaction;
use App\Services\Payments\PaymentReversalService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ProcessMpesaReversal
 *
 * Handles the reversal of a previously successful inbound payment.
 *
 * Triggered when:
 *  a) Daraja sends a reversal_result callback (M-Pesa-initiated reversal).
 *  b) An admin manually triggers a reversal from the reconciliation UI.
 *
 * The job delegates to PaymentReversalService which:
 *  - Stamps the LoanPayment as reversed.
 *  - Stamps the MpesaTransaction as reversed.
 *  - Restores schedule outstanding balances (via LoanBalanceService).
 *  - Reclaims any unapplied credit from the borrower's wallet.
 *
 * Uses the default queue consumed by the application's running workers.
 * Retried 3 times.
 */
class ProcessMpesaReversal implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int   $tries   = 3;
    public int   $timeout = 60;
    public array $backoff  = [10, 60, 300];

    public function __construct(
        public readonly int    $mpesaTransactionId,
        public readonly int    $organisationId,
        public readonly string $reason = '',
    ) {
    }

    public function handle(PaymentReversalService $reversalService): void
    {
        $tx = MpesaTransaction::where('organisation_id', $this->organisationId)
            ->find($this->mpesaTransactionId);

        if (!$tx) {
            Log::warning('ProcessMpesaReversal: transaction not found', [
                'id' => $this->mpesaTransactionId,
            ]);
            return;
        }

        if (empty($tx->mpesa_receipt_number)) {
            Log::warning('ProcessMpesaReversal: transaction has no receipt number — cannot reverse', [
                'id' => $tx->id,
            ]);
            return;
        }

        try {
            $reversalService->reverseByReceipt(
                mpesaReceiptNumber: $tx->mpesa_receipt_number,
                organisationId:     $this->organisationId,
                reason:             $this->reason ?: 'M-Pesa reversal callback',
            );
        } catch (\RuntimeException $e) {
            // Payment not found (possibly never matched) — log and move on
            Log::warning('ProcessMpesaReversal: ' . $e->getMessage(), [
                'tx_id' => $tx->id,
            ]);
        }

        // Mark transaction as reversed regardless of payment lookup outcome
        $tx->update(['status' => 'reversed']);

        Log::info('ProcessMpesaReversal: reversal processed', [
            'tx_id'   => $tx->id,
            'receipt' => $tx->mpesa_receipt_number,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('ProcessMpesaReversal: job failed permanently', [
            'tx_id' => $this->mpesaTransactionId,
            'error' => $e->getMessage(),
        ]);
    }
}
