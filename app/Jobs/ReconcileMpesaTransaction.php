<?php

namespace App\Jobs;

use App\Models\MpesaTransaction;
use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ReconcileMpesaTransaction
 *
 * Queued when an inbound MpesaTransaction could not be automatically matched
 * to a borrower or loan by MpesaPaymentMatcher.
 *
 * Responsibilities
 * ────────────────
 *  1. Ensure the transaction's status is set to 'received' so it surfaces in
 *     the reconciliation dashboard.
 *  2. (Future) Attempt a second-pass match using fuzzy/enriched strategies
 *     (e.g. amount + date proximity, partial reference matching).
 *  3. Emit a log entry / notification so the operations team is alerted.
 *
 * The admin reconciliation UI then allows a human to:
 *   - Select the correct borrower + loan.
 *   - Confirm the match.
 *   - Dispatch AllocateLoanPayment to complete the lifecycle.
 *
 * Queued on 'reconciliation' (lower priority than 'mpesa').
 * Does not retry automatically — unmatched transactions require human review.
 */
class ReconcileMpesaTransaction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;
    public int $timeout = 30;

    public function __construct(
        public readonly int $mpesaTransactionId,
        public readonly int $organisationId,
    ) {
        $this->onQueue('reconciliation');
    }

    public function handle(): void
    {
        $tx = MpesaTransaction::where('organisation_id', $this->organisationId)
            ->find($this->mpesaTransactionId);

        if (!$tx) {
            Log::warning('ReconcileMpesaTransaction: transaction not found', [
                'id' => $this->mpesaTransactionId,
            ]);
            return;
        }

        // Ensure the transaction is in the 'received' holding state
        if (!in_array($tx->status, ['received', 'success'], true)) {
            Log::info('ReconcileMpesaTransaction: transaction in unexpected status — leaving as-is', [
                'id'     => $tx->id,
                'status' => $tx->status,
            ]);
            return;
        }

        // Mark as received (awaiting manual reconciliation)
        $tx->update(['status' => 'received']);

        Log::info('ReconcileMpesaTransaction: transaction queued for manual reconciliation', [
            'id'              => $tx->id,
            'amount_cents'    => $tx->amount_cents,
            'phone'           => $tx->phone_number,
            'account_ref'     => $tx->account_reference,
            'receipt'         => $tx->mpesa_receipt_number,
            'organisation_id' => $this->organisationId,
        ]);

        // Dispatch in-app notification to all admin/operations users in this org
        $this->notifyOperationsTeam($tx);
    }

    // ── Private helpers ───────────────────────────────────────────────────

    /**
     * Create an in-app Notification row for every active admin/ops user in
     * the organisation so they see the unmatched payment in the UI.
     */
    private function notifyOperationsTeam(MpesaTransaction $tx): void
    {
        try {
            $amountKes = number_format($tx->amount_cents / 100, 2);
            $receipt   = $tx->mpesa_receipt_number ?? 'N/A';
            $phone     = $tx->phone_number ?? 'unknown';

            // Target all active users with admin / super-admin / operations roles in this org
            $userIds = DB::table('users')
                ->join('role_user', 'role_user.user_id', '=', 'users.id')
                ->join('roles', 'roles.id', '=', 'role_user.role_id')
                ->where('users.organisation_id', $this->organisationId)
                ->where('users.is_active', true)
                ->whereIn('roles.name', ['admin', 'super-admin', 'operations'])
                ->pluck('users.id')
                ->unique();

            if ($userIds->isEmpty()) {
                // Fall back: notify all active users in the org
                $userIds = DB::table('users')
                    ->where('organisation_id', $this->organisationId)
                    ->where('is_active', true)
                    ->pluck('id');
            }

            foreach ($userIds as $userId) {
                Notification::create([
                    'organisation_id'   => $this->organisationId,
                    'user_id'           => $userId,
                    'borrower_id'       => $tx->borrower_id,
                    'channel'           => 'in_app',
                    'notification_type' => 'unmatched_payment',
                    'title'             => 'Unmatched M-Pesa payment',
                    'message'           => "KES {$amountKes} received from {$phone} (receipt {$receipt}) could not be matched to a loan. Manual reconciliation required.",
                    'data'              => [
                        'mpesa_transaction_id' => $tx->id,
                        'amount_cents'         => $tx->amount_cents,
                        'phone_number'         => $phone,
                        'receipt'              => $receipt,
                        'account_reference'    => $tx->account_reference,
                    ],
                    'status'            => 'sent',
                    'sent_at'           => now(),
                    'reference_type'    => MpesaTransaction::class,
                    'reference_id'      => $tx->id,
                ]);
            }

            Log::info('ReconcileMpesaTransaction: notifications dispatched', [
                'tx_id'          => $tx->id,
                'notified_users' => $userIds->count(),
            ]);
        } catch (\Throwable $e) {
            // Notification failure must not fail the reconciliation job
            Log::warning('ReconcileMpesaTransaction: notification dispatch failed', [
                'tx_id' => $tx->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('ReconcileMpesaTransaction: job failed', [
            'tx_id' => $this->mpesaTransactionId,
            'error' => $e->getMessage(),
        ]);
    }
}
