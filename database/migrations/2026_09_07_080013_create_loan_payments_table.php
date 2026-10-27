<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organisation_id')
                ->constrained('organisations')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->unsignedBigInteger('loan_id')->nullable();

            $table->unsignedBigInteger('loan_schedule_id')
                ->nullable();

            $table->string('payment_method', 30)
                ->default('mpesa');

            $table->string('external_reference', 100)
                ->nullable();

            $table->string('mpesa_receipt_number', 100)
                ->nullable();

            $table->bigInteger('amount_cents')
                ->unsigned();

            $table->string('status', 30)
                ->default('posted');

            $table->timestamp('received_at')
                ->nullable();

            $table->timestamp('posted_at')
                ->nullable();

            $table->timestamp('reversed_at')
                ->nullable();

            // Original provider response for audit/reconciliation.
            $table->json('provider_payload')
                ->nullable();

            $table->timestamps();

            // M-Pesa receipts should be unique within an organisation.
            $table->unique([
                'organisation_id',
                'mpesa_receipt_number',
            ], 'uniq_loan_payments_organisation_id_mpesa_receipt_number');;

            // Required for tenant-safe references from payment_allocations.
            $table->unique([
                'organisation_id',
                'id',
            ], 'uniq_loan_payments_organisation_id_id');;

            $table->index([
                'organisation_id',
                'loan_id',
                'status',
            ], 'idx_loan_payments_organisation_id_loan_id_status');;

            $table->index([
                'organisation_id',
                'received_at',
            ], 'idx_loan_payments_organisation_id_received_at');;

            // Tenant-safe relationship to loans.
            $table->foreign(
                ['organisation_id', 'loan_id'],
                'loan_payments_org_loan_fk'
            )->references(
                ['organisation_id', 'id']
            )->on('loans')
             ->cascadeOnUpdate()
             ->restrictOnDelete();

            // Tenant-safe relationship to schedules.
            $table->foreign(
                ['organisation_id', 'loan_schedule_id'],
                'loan_payments_org_schedule_fk'
            )->references(
                ['organisation_id', 'id']
            )->on('loan_schedules')
             ->cascadeOnUpdate()
             ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_payments');
    }
};
