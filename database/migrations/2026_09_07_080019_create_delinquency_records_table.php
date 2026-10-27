<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delinquency_records', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organisation_id')
                ->constrained('organisations')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->unsignedBigInteger('borrower_id');
            $table->unsignedBigInteger('loan_id');
            $table->unsignedBigInteger('loan_schedule_id');

            $table->date('due_date');
            $table->date('delinquent_from');

            $table->date('cleared_at')->nullable();

            $table->unsignedInteger('days_overdue')->default(0);

            // Outstanding amount attributable to this installment.
            $table->unsignedBigInteger('amount_due_cents')->default(0);
            $table->unsignedBigInteger('amount_paid_cents')->default(0);
            $table->unsignedBigInteger('amount_outstanding_cents')->default(0);

            $table->string('status', 30)->default('active');

            // Snapshot of the delinquency state for audit/reprocessing.
            $table->json('record_snapshot')->nullable();

            $table->timestamps();

            $table->unique(['organisation_id', 'id'], 'uniq_delinquency_records_organisation_id_id');;

            // Normally one active/historical delinquency record per schedule.
            $table->unique([
                'organisation_id',
                'loan_schedule_id',
            ], 'uniq_delinquency_records_organisation_id_loan_schedule_id');;

            $table->index([
                'organisation_id',
                'borrower_id',
                'status',
                'delinquent_from',
            ], 'idx_delinquency_records_organisation_id_borrower_id_status_deli');;

            $table->index([
                'organisation_id',
                'loan_id',
                'status',
            ], 'idx_delinquency_records_organisation_id_loan_id_status');;

            $table->index([
                'organisation_id',
                'status',
                'days_overdue',
            ], 'idx_delinquency_records_organisation_id_status_days_overdue');;

            $table->foreign(
                ['organisation_id', 'borrower_id'],
                'delinquency_records_org_borrower_fk'
            )->references(
                ['organisation_id', 'id']
            )->on('borrowers')
             ->cascadeOnUpdate()
             ->restrictOnDelete();

            $table->foreign(
                ['organisation_id', 'loan_id'],
                'delinquency_records_org_loan_fk'
            )->references(
                ['organisation_id', 'id']
            )->on('loans')
             ->cascadeOnUpdate()
             ->restrictOnDelete();

            $table->foreign(
                ['organisation_id', 'loan_schedule_id'],
                'delinquency_records_org_schedule_fk'
            )->references(
                ['organisation_id', 'id']
            )->on('loan_schedules')
             ->cascadeOnUpdate()
             ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delinquency_records');
    }
};
