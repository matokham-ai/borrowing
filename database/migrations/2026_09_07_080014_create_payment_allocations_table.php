<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organisation_id')
                ->constrained('organisations')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->unsignedBigInteger('loan_payment_id');

            $table->unsignedBigInteger('loan_schedule_id');

            // principal, interest, fees, penalty, other
            $table->string('allocation_type', 30);

            $table->unsignedBigInteger('amount_cents');

            $table->timestamps();

            // One component can only be allocated once per
            // payment/schedule combination.
            $table->unique([
                'loan_payment_id',
                'loan_schedule_id',
                'allocation_type',
            ], 'uniq_payment_allocations_loan_payment_id_loan_schedule_id_alloc');;

            // Required for tenant-safe downstream relationships.
            $table->unique([
                'organisation_id',
                'id',
            ], 'uniq_payment_allocations_organisation_id_id');;

            $table->index([
                'organisation_id',
                'loan_schedule_id',
                'allocation_type',
            ], 'idx_payment_allocations_organisation_id_loan_schedule_id_alloca');;

            // Tenant-safe relationship to the payment.
            $table->foreign(
                ['organisation_id', 'loan_payment_id'],
                'payment_allocations_org_payment_fk'
            )->references(
                ['organisation_id', 'id']
            )->on('loan_payments')
             ->cascadeOnUpdate()
             ->restrictOnDelete();

            // Tenant-safe relationship to the schedule.
            $table->foreign(
                ['organisation_id', 'loan_schedule_id'],
                'payment_allocations_org_schedule_fk'
            )->references(
                ['organisation_id', 'id']
            )->on('loan_schedules')
             ->cascadeOnUpdate()
             ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');
    }
};