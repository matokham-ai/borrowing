<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('default_records', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organisation_id')
                ->constrained('organisations')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->unsignedBigInteger('borrower_id');
            $table->unsignedBigInteger('loan_id');

            $table->string('reason', 100)->nullable();

            $table->date('defaulted_at');

            $table->unsignedInteger('days_overdue')->default(0);

            // Snapshot of the financial position at default.
            $table->unsignedBigInteger('principal_outstanding_cents')->default(0);
            $table->unsignedBigInteger('interest_outstanding_cents')->default(0);
            $table->unsignedBigInteger('fees_outstanding_cents')->default(0);
            $table->unsignedBigInteger('penalty_outstanding_cents')->default(0);
            $table->unsignedBigInteger('total_outstanding_cents')->default(0);

            $table->string('status', 30)->default('active');

            $table->date('resolved_at')->nullable();
            $table->string('resolution_type', 50)->nullable();

            // Recovery/write-off information without deleting the default history.
            $table->unsignedBigInteger('amount_recovered_cents')->default(0);
            $table->unsignedBigInteger('amount_written_off_cents')->default(0);

            $table->json('default_snapshot')->nullable();

            $table->timestamps();

            $table->unique(['organisation_id', 'id'], 'uniq_default_records_organisation_id_id');;

            // A loan has one default record; subsequent recovery/resolution
            // is tracked on this historical record.
            $table->unique([
                'organisation_id',
                'loan_id',
            ], 'uniq_default_records_organisation_id_loan_id');;

            $table->index([
                'organisation_id',
                'borrower_id',
                'status',
            ], 'idx_default_records_organisation_id_borrower_id_status');;

            $table->index([
                'organisation_id',
                'defaulted_at',
            ], 'idx_default_records_organisation_id_defaulted_at');;

            $table->foreign(
                ['organisation_id', 'borrower_id'],
                'default_records_org_borrower_fk'
            )->references(
                ['organisation_id', 'id']
            )->on('borrowers')
             ->cascadeOnUpdate()
             ->restrictOnDelete();

            $table->foreign(
                ['organisation_id', 'loan_id'],
                'default_records_org_loan_fk'
            )->references(
                ['organisation_id', 'id']
            )->on('loans')
             ->cascadeOnUpdate()
             ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('default_records');
    }
};
