<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('behaviour_events', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organisation_id')
                ->constrained('organisations')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->unsignedBigInteger('borrower_id');
            $table->unsignedBigInteger('loan_id')->nullable();
            $table->unsignedBigInteger('loan_schedule_id')->nullable();
            $table->unsignedBigInteger('loan_payment_id')->nullable();

            // Examples:
            // loan_repaid_on_time, loan_paid_early, payment_late,
            // missed_installment, partial_payment, default, recovery.
            $table->string('event_type', 50);

            // Positive or negative behavioural impact.
            $table->decimal('score_impact', 8, 4)->default(0);

            $table->timestamp('occurred_at');

            // Prevents the same source event from being scored twice.
            $table->string('idempotency_key', 150);

            // Context used to explain/reproduce the event.
            $table->json('event_data')->nullable();

            $table->timestamps();

            $table->unique([
                'organisation_id',
                'idempotency_key',
            ], 'uniq_behaviour_events_organisation_id_idempotency_key');;

            $table->unique(['organisation_id', 'id'], 'uniq_behaviour_events_organisation_id_id');;

            $table->index([
                'organisation_id',
                'borrower_id',
                'occurred_at',
            ], 'idx_behaviour_events_organisation_id_borrower_id_occurred_at');;

            $table->index([
                'organisation_id',
                'borrower_id',
                'event_type',
            ], 'idx_behaviour_events_organisation_id_borrower_id_event_type');;

            $table->index([
                'organisation_id',
                'loan_id',
                'occurred_at',
            ], 'idx_behaviour_events_organisation_id_loan_id_occurred_at');;

            $table->foreign(
                ['organisation_id', 'borrower_id'],
                'behaviour_events_org_borrower_fk'
            )->references(
                ['organisation_id', 'id']
            )->on('borrowers')
             ->cascadeOnUpdate()
             ->restrictOnDelete();

            $table->foreign(
                ['organisation_id', 'loan_id'],
                'behaviour_events_org_loan_fk'
            )->references(
                ['organisation_id', 'id']
            )->on('loans')
             ->cascadeOnUpdate()
             ->restrictOnDelete();

            $table->foreign(
                ['organisation_id', 'loan_schedule_id'],
                'behaviour_events_org_schedule_fk'
            )->references(
                ['organisation_id', 'id']
            )->on('loan_schedules')
             ->cascadeOnUpdate()
             ->restrictOnDelete();

            $table->foreign(
                ['organisation_id', 'loan_payment_id'],
                'behaviour_events_org_payment_fk'
            )->references(
                ['organisation_id', 'id']
            )->on('loan_payments')
             ->cascadeOnUpdate()
             ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('behaviour_events');
    }
};
