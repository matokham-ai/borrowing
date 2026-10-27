<?php

namespace App\Services\Payments;

use App\Models\Borrower;
use App\Models\LoanPayment;
use App\Models\MpesaTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PaymentReversalService
 *
 * Handles M-Pesa reversals and manual payment reversals.
 *
 * Reversal philosophy
 * ───────────────────
 * We never delete or mutate the original payment record — it must remain
 * part of the immutable audit history.  Instead:
 *
 *  1. The original LoanPayment is stamped status='reversed', reversed_at=now().
 *  2. The mpesa_transaction is stamped status='reversed'.
 *  3. LoanBalanceService.refreshAfterPayment() is re-called — it only sums
 *     allocations from payments with status='posted', so the reversed
 *     payment's allocations are automatically excluded, restoring the correct
 *     outstanding balances on the schedule rows.
 *  4. If the reversed payment had unapplied_cents, those are deducted from
 *     the borrower's credit wallet.
 *
 * No counter-allocation rows are created: exclusion-by-status is cleaner and
 * avoids sign confusion in the allocation table.
 */
class PaymentReversalService
{
    public function __construct(
        private readonly LoanBalanceService $loanBalanceService,
    ) {}

    // ── Public API ────────────────────────────────────────────────────────

    /**
     * Reverse a LoanPayment identified by its M-Pesa receipt number.
     *
     * Called when M-Pesa sends a reversal result callback (via
     * ProcessMpesaTransaction → ProcessMpesaReversal job).
     *
     * @throws \RuntimeException when the payment cannot be found or is
     *                           already reversed.
     */
    public function reverseByReceipt(
        string $mpesaReceiptNumber,
        int    $organisationId,
        string $reason = '',
    ): LoanPayment {
        $payment = LoanPayment::where('organisation_id', $organisationId)
            ->where('mpesa_receipt_number', $mpesaReceiptNumber)
            ->first();

        if (!$payment) {
            throw new \RuntimeException(
                "Cannot reverse: no LoanPayment found with receipt '{$mpesaReceiptNumber}' "
                . "for organisation {$organisationId}."
            );
        }

        return $this->reversePayment($payment, $reason);
    }

    /**
     * Reverse a LoanPayment directly by model instance.
     *
     * Used for manual reversals triggered by an admin action.
     */
    public function reversePayment(
        LoanPayment $payment,
        string      $reason = '',
    ): LoanPayment {
        if ($payment->status === 'reversed') {
            Log::info('PaymentReversalService: payment already reversed, skipping', [
                'loan_payment_id' => $payment->id,
            ]);
            return $payment;
        }

        if ($payment->status !== 'posted') {
            throw new \RuntimeException(
                "Cannot reverse LoanPayment #{$payment->id}: status is '{$payment->status}', expected 'posted'."
            );
        }

        DB::transaction(function () use ($payment, $reason) {
            // ── 1. Stamp the payment as reversed ─────────────────────────
            $payment->update([
                'status'          => 'reversed',
                'reversed_at'     => now(),
                'reversal_reason' => $reason ?: 'M-Pesa reversal',
            ]);

            // ── 2. Stamp the originating mpesa_transaction (if any) ───────
            if ($payment->mpesa_transaction_id) {
                MpesaTransaction::where('id', $payment->mpesa_transaction_id)
                    ->update(['status' => 'reversed']);
            }

            // ── 3. Reclaim unapplied balance from borrower wallet ─────────
            if ((int) $payment->unapplied_cents > 0 && $payment->borrower_id) {
                // Use DB::table() to bypass the MultiTenantTrait global scope
                // which requires an authenticated user (not available in queued jobs).
                DB::table('borrowers')
                    ->where('organisation_id', $payment->organisation_id)
                    ->where('id', $payment->borrower_id)
                    ->decrement('unapplied_balance_cents', (int) $payment->unapplied_cents);
            }

            // ── 4. Refresh schedule balances (reversal excluded by status) ─
            // We pass a minimal stub payment whose loan_id and organisation_id
            // are used to identify which loan's schedules to re-aggregate.
            $this->loanBalanceService->refreshAfterPayment($payment);
        });

        Log::info('PaymentReversalService: payment reversed', [
            'loan_payment_id'  => $payment->id,
            'loan_id'          => $payment->loan_id,
            'amount_cents'     => $payment->amount_cents,
            'reason'           => $reason,
        ]);

        return $payment;
    }
}
