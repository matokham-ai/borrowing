<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mpesa_transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organisation_id')
                ->constrained('organisations')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // Optional links into the lending domain.
            $table->unsignedBigInteger('borrower_id')->nullable();
            $table->unsignedBigInteger('loan_id')->nullable();
            $table->unsignedBigInteger('loan_payment_id')->nullable();

            $table->string('transaction_type', 30);
            // c2b, stk_push, b2c

            $table->string('direction', 20);
            // inbound, outbound

            $table->string('status', 30)->default('received');
            // received, submitted, processing, success, failed,
            // timeout, reversed, reconciled

            // Daraja/provider identifiers.
            $table->string('mpesa_receipt_number', 100)->nullable();
            $table->string('merchant_request_id', 150)->nullable();
            $table->string('checkout_request_id', 150)->nullable();
            $table->string('originator_conversation_id', 150)->nullable();
            $table->string('conversation_id', 150)->nullable();
            $table->string('external_reference', 150)->nullable();

            // Financial information is always stored as integer cents.
            $table->unsignedBigInteger('amount_cents')->nullable();

            $table->string('currency', 3)->default('KES');

            // Party information.
            $table->string('phone_number', 30)->nullable();
            $table->string('payee_name', 255)->nullable();
            $table->string('sender_name', 255)->nullable();
            $table->string('receiver_name', 255)->nullable();

            // C2B/STK account or bill reference.
            $table->string('account_reference', 150)->nullable();

            // Provider transaction timestamp normalized by the integration service.
            $table->timestamp('transacted_at')->nullable();

            // Raw provider response/callback retained for audit.
            $table->json('provider_payload')->nullable();

            // Normalized provider result/message.
            $table->string('result_code', 50)->nullable();
            $table->text('result_description')->nullable();

            // Idempotency/reconciliation.
            $table->string('idempotency_key', 200);
            $table->timestamp('received_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('reconciled_at')->nullable();

            $table->timestamps();

            $table->unique([
                'organisation_id',
                'idempotency_key',
            ], 'uniq_mpesa_transactions_organisation_id_idempotency_key');;

            $table->unique([
                'organisation_id',
                'id',
            ], 'uniq_mpesa_transactions_organisation_id_id');;

            $table->unique([
                'organisation_id',
                'mpesa_receipt_number',
            ], 'uniq_mpesa_transactions_organisation_id_mpesa_receipt_number');;

            $table->index([
                'organisation_id',
                'transaction_type',
                'status',
            ], 'idx_mpesa_transactions_organisation_id_transaction_type_status');;

            $table->index([
                'organisation_id',
                'loan_id',
                'status',
            ], 'idx_mpesa_transactions_organisation_id_loan_id_status');;

            $table->index([
                'organisation_id',
                'borrower_id',
                'transacted_at',
            ], 'idx_mpesa_transactions_organisation_id_borrower_id_transacted_a');;

            $table->index([
                'organisation_id',
                'checkout_request_id',
            ], 'idx_mpesa_transactions_organisation_id_checkout_request_id');;

            $table->index([
                'organisation_id',
                'conversation_id',
            ], 'idx_mpesa_transactions_organisation_id_conversation_id');;

            $table->index([
                'organisation_id',
                'transacted_at',
            ], 'idx_mpesa_transactions_organisation_id_transacted_at');;

            $table->foreign(
                ['organisation_id', 'borrower_id'],
                'mpesa_transactions_org_borrower_fk'
            )->references(
                ['organisation_id', 'id']
            )->on('borrowers')
             ->cascadeOnUpdate()
             ->restrictOnDelete();

            $table->foreign(
                ['organisation_id', 'loan_id'],
                'mpesa_transactions_org_loan_fk'
            )->references(
                ['organisation_id', 'id']
            )->on('loans')
             ->cascadeOnUpdate()
             ->restrictOnDelete();

            $table->foreign(
                ['organisation_id', 'loan_payment_id'],
                'mpesa_transactions_org_payment_fk'
            )->references(
                ['organisation_id', 'id']
            )->on('loan_payments')
             ->cascadeOnUpdate()
             ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mpesa_transactions');
    }
};
