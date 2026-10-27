<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_schedules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organisation_id')
                ->constrained('organisations')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->unsignedBigInteger('loan_id');

            $table->unsignedInteger('installment_number');
            $table->date('due_date');

            // Contractual amount due.
            $table->bigInteger('principal_due_cents')
                ->unsigned()
                ->default(0);

            $table->bigInteger('interest_due_cents')
                ->unsigned()
                ->default(0);

            $table->bigInteger('fees_due_cents')
                ->unsigned()
                ->default(0);

            // Penalties accrued after the original due date.
            $table->bigInteger('penalty_due_cents')
                ->unsigned()
                ->default(0);

            $table->bigInteger('total_due_cents')
                ->unsigned();

            // Amounts actually allocated to this installment.
            $table->bigInteger('principal_paid_cents')
                ->unsigned()
                ->default(0);

            $table->bigInteger('interest_paid_cents')
                ->unsigned()
                ->default(0);

            $table->bigInteger('fees_paid_cents')
                ->unsigned()
                ->default(0);

            $table->bigInteger('penalty_paid_cents')
                ->unsigned()
                ->default(0);

            $table->bigInteger('total_paid_cents')
                ->unsigned()
                ->default(0);

            $table->string('status', 30)
                ->default('pending');

            $table->timestamp('paid_at')->nullable();

            $table->timestamps();

            // A loan cannot have two installments with the same number.
            $table->unique([
                'organisation_id',
                'loan_id',
                'installment_number',
            ], 'uniq_loan_schedules_organisation_id_loan_id_installment_number');;

            $table->unique([
                'organisation_id',
                'id',
            ], 'uniq_loan_schedules_organisation_id_id');;

            $table->index([
                'organisation_id',
                'loan_id',
                'due_date',
            ], 'idx_loan_schedules_organisation_id_loan_id_due_date');;

            $table->index([
                'organisation_id',
                'status',
                'due_date',
            ], 'idx_loan_schedules_organisation_id_status_due_date');;

            // Tenant-safe relationship to loans.
            $table->foreign(
                ['organisation_id', 'loan_id'],
                'loan_schedules_org_loan_fk'
            )->references(
                ['organisation_id', 'id']
            )->on('loans')
             ->cascadeOnUpdate()
             ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_schedules');
    }
};