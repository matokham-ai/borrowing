<?php

namespace App\Services\Payments;

use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\MpesaTransaction;
use Illuminate\Support\Facades\DB;

/**
 * PaymentService
 *
 * Converts a validated, matched MpesaTransaction into an official LoanPayment.
 *
 * Separation of concerns
 * ──────────────────────
 *   MpesaTransaction → answers "what did M-Pesa tell us happened?"
 *   LoanPayment      → answers "what payment did we recognise against this loan?"
 *
 * This service sits at the bridge between the two layers.
 * Allocation is NOT performed here — that is PaymentAllocationService's job.
 *
 * Idempotency
 * ───────────
 * A LoanPayment is uniquely constrained on (organisation_id, mpesa_receipt_number).
 * Calling createFromTransaction() for a receipt that already has a payment
 * returns the existing row rather than creating a duplicate.
 */
class PaymentService
{
    // ── Public API ───────────────────────────────────────────────────────

    /**
     * Create a LoanPayment from a successfully matched MpesaTransaction.
     *
     * Preconditions (caller must guarantee):
     *   - $tx->status === 'success'
     *   - $tx->loan_id is not null
     *   - $tx->borrower_id is not null
     *   - $tx->amount_cents > 0
     *
     * @throws \RuntimeException when preconditions are violated.
     */
    public function createFromTransaction(MpesaTransaction $tx): LoanPayment
    {
        $this->assertReadyForPayment($tx);

        // Idempotency: return existing payment if receipt already posted
        if (!empty($tx->mpesa_receipt_number)) {
            $existing = LoanPayment::where('organisation_id', $tx->organisation_id)
                ->where('mpesa_receipt_number', $tx->mpesa_receipt_number)
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        return DB::transaction(function () use ($tx) {
            // ── Create the loan payment record ───────────────────────────
            $payment = LoanPayment::create([
                'organisation_id'     => $tx->organisation_id,
                'loan_id'             => $tx->loan_id,
                'borrower_id'         => $tx->borrower_id,
                'mpesa_transaction_id'=> $tx->id,
                'payment_method'      => 'mpesa',
                'mpesa_receipt_number'=> $tx->mpesa_receipt_number,
                'external_reference'  => $tx->mpesa_receipt_number,
                'amount_cents'        => $tx->amount_cents,
                'unapplied_cents'     => 0,             // filled in by PaymentAllocationService
                'status'              => 'posted',
                'received_at'         => $tx->transacted_at ?? $tx->received_at ?? now(),
                'posted_at'           => now(),
                'provider_payload'    => $tx->provider_payload,
            ]);

            // ── Link the mpesa_transaction back to this payment ───────────
            $tx->update([
                'loan_payment_id' => $payment->id,
                'processed_at'    => now(),
            ]);

            return $payment;
        });
    }

    /**
     * Create a manual LoanPayment (cash, bank transfer, etc.).
     *
     * Used for reconciliation when a payment is matched by an admin
     * and does not originate from an MpesaTransaction.
     */
    public function createManual(
        int    $organisationId,
        int    $loanId,
        int    $borrowerId,
        int    $amountCents,
        string $paymentMethod = 'cash',
        string $externalReference = '',
    ): LoanPayment {
        if ($amountCents <= 0) {
            throw new \RuntimeException('Manual payment amount must be greater than zero.');
        }

        $loan = Loan::where('organisation_id', $organisationId)->findOrFail($loanId);

        return LoanPayment::create([
            'organisation_id'     => $organisationId,
            'loan_id'             => $loan->id,
            'borrower_id'         => $borrowerId,
            'mpesa_transaction_id'=> null,
            'payment_method'      => $paymentMethod,
            'mpesa_receipt_number'=> null,
            'external_reference'  => $externalReference ?: null,
            'amount_cents'        => $amountCents,
            'unapplied_cents'     => 0,
            'status'              => 'posted',
            'received_at'         => now(),
            'posted_at'           => now(),
        ]);
    }

    /** Create a pending holding row for an inbound transaction awaiting reconciliation. */
    public function createUnmatchedFromTransaction(MpesaTransaction $tx): LoanPayment
    {
        if ($tx->direction !== 'inbound' || !in_array($tx->status, ['success', 'received'], true)) {
            throw new \RuntimeException('Only successful or received inbound transactions can be held for reconciliation.');
        }

        $existing = LoanPayment::where('organisation_id', $tx->organisation_id)
            ->where('mpesa_transaction_id', $tx->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $payment = LoanPayment::firstOrCreate(
            [
                'organisation_id' => $tx->organisation_id,
                'mpesa_receipt_number' => $tx->mpesa_receipt_number,
            ],
            [
                'loan_id' => null,
                'borrower_id' => null,
                'mpesa_transaction_id' => $tx->id,
                'payment_method' => 'mpesa',
                'external_reference' => $tx->account_reference ?: $tx->mpesa_receipt_number,
                'amount_cents' => $tx->amount_cents,
                'unapplied_cents' => $tx->amount_cents,
                'status' => 'pending',
                'received_at' => $tx->transacted_at ?? $tx->received_at ?? now(),
                'provider_payload' => $tx->provider_payload,
            ]
        );

        $tx->update(['loan_payment_id' => $payment->id]);

        return $payment;
    }

    // ── Precondition assertions ───────────────────────────────────────────

    private function assertReadyForPayment(MpesaTransaction $tx): void
    {
        if ((string) $tx->status !== 'success') {
            throw new \RuntimeException(
                "Cannot create payment: MpesaTransaction #{$tx->id} has status '{$tx->status}', expected 'success'."
            );
        }

        if (empty($tx->loan_id)) {
            throw new \RuntimeException(
                "Cannot create payment: MpesaTransaction #{$tx->id} has no loan_id. Run MpesaPaymentMatcher first."
            );
        }

        if (empty($tx->borrower_id)) {
            throw new \RuntimeException(
                "Cannot create payment: MpesaTransaction #{$tx->id} has no borrower_id."
            );
        }

        if ((int) $tx->amount_cents <= 0) {
            throw new \RuntimeException(
                "Cannot create payment: MpesaTransaction #{$tx->id} has amount_cents = {$tx->amount_cents}."
            );
        }
    }
}
