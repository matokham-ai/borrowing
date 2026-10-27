<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Payment-lifecycle schema additions.
 *
 * Columns added:
 *
 * loans
 *   repayment_reference  — unique human-readable reference the borrower quotes
 *                          when paying (e.g. "MAV-00001245"). Used to match
 *                          incoming C2B / STK callbacks to the correct loan.
 *   disbursement_mpesa_transaction_id — FK to mpesa_transactions for the B2C
 *                                       outgoing disbursement.
 *
 * borrowers
 *   unapplied_balance_cents — running credit balance from overpayments. Any
 *                             surplus that exceeds the outstanding obligation is
 *                             held here and applied to the next instalment.
 *
 * loan_payments
 *   borrower_id          — denormalised for faster queries / reconciliation UIs.
 *   mpesa_transaction_id — FK to the mpesa_transaction that originated this
 *                          payment. Null when the payment is entered manually.
 *   unapplied_cents      — portion of the payment that exceeded all outstanding
 *                          obligations and was not allocated to any schedule row.
 *                          Added to borrower.unapplied_balance_cents.
 *   reversal_reason      — free-text reason recorded when status = 'reversed'.
 *
 * loan_products
 *   payment_waterfall    — JSON array of allocation component names in priority
 *                          order. Defaults to ["penalty","fees","interest","principal"].
 *                          Allows per-product overrides without code changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── loans ────────────────────────────────────────────────────────
        Schema::table('loans', function (Blueprint $table) {
            $table->string('repayment_reference', 30)
                ->nullable()
                ->unique()
                ->after('status')
                ->comment('Unique borrower-facing payment reference, e.g. MAV-00001245');

            $table->unsignedBigInteger('disbursement_mpesa_transaction_id')
                ->nullable()
                ->after('repayment_reference')
                ->comment('FK to the outbound B2C mpesa_transaction');

            $table->index('repayment_reference', 'idx_loans_repayment_reference');
        });

        // ── borrowers ────────────────────────────────────────────────────
        Schema::table('borrowers', function (Blueprint $table) {
            $table->unsignedBigInteger('unapplied_balance_cents')
                ->default(0)
                ->after('last_repayment_at')
                ->comment('Credit balance from overpayments; applied to next instalment');
        });

        // ── loan_payments ────────────────────────────────────────────────
        Schema::table('loan_payments', function (Blueprint $table) {
            // Denormalised borrower link for easy lookups/reconciliation
            $table->unsignedBigInteger('borrower_id')
                ->nullable()
                ->after('loan_id');

            // Link back to the originating M-Pesa transaction
            $table->unsignedBigInteger('mpesa_transaction_id')
                ->nullable()
                ->after('borrower_id');

            // Overpayment holding bucket
            $table->unsignedBigInteger('unapplied_cents')
                ->default(0)
                ->after('amount_cents')
                ->comment('Portion of payment not allocated to any schedule row');

            // Reversal metadata
            $table->text('reversal_reason')
                ->nullable()
                ->after('reversed_at');

            $table->index(
                ['organisation_id', 'borrower_id'],
                'idx_loan_payments_organisation_id_borrower_id'
            );

            $table->index(
                ['organisation_id', 'mpesa_transaction_id'],
                'idx_loan_payments_organisation_id_mpesa_transaction_id'
            );
        });

        // ── loan_products ────────────────────────────────────────────────
        Schema::table('loan_products', function (Blueprint $table) {
            $table->json('payment_waterfall')
                ->nullable()
                ->after('grace_period_days')
                ->comment('Allocation order: e.g. ["penalty","fees","interest","principal"]');
        });
    }

    public function down(): void
    {
        Schema::table('loan_products', function (Blueprint $table) {
            $table->dropColumn('payment_waterfall');
        });

        Schema::table('loan_payments', function (Blueprint $table) {
            $table->dropIndex('idx_loan_payments_organisation_id_borrower_id');
            $table->dropIndex('idx_loan_payments_organisation_id_mpesa_transaction_id');
            $table->dropColumn(['borrower_id', 'mpesa_transaction_id', 'unapplied_cents', 'reversal_reason']);
        });

        Schema::table('borrowers', function (Blueprint $table) {
            $table->dropColumn('unapplied_balance_cents');
        });

        Schema::table('loans', function (Blueprint $table) {
            $table->dropIndex('idx_loans_repayment_reference');
            $table->dropColumn(['repayment_reference', 'disbursement_mpesa_transaction_id']);
        });
    }
};
