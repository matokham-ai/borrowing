<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_analysis_runs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organisation_id')
                ->constrained('organisations')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('borrower_id')
                ->constrained('borrowers')
                ->restrictOnDelete();

            $table->foreignId('statement_document_id')
                ->nullable()
                ->constrained('statement_documents')
                ->restrictOnDelete();

            $table->string('run_type', 50)->default('appraisal');
            $table->string('status', 30)->default('queued');

            $table->string('model_version', 100)->nullable();
            $table->string('parser_version', 100)->nullable();
            $table->string('classifier_version', 100)->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->unsignedInteger('transaction_count')->default(0);
            $table->unsignedInteger('classified_transaction_count')->default(0);

            $table->decimal('financial_score', 8, 4)->nullable();

            $table->json('input_snapshot')->nullable();
            $table->json('result_snapshot')->nullable();
            $table->json('error_snapshot')->nullable();

            $table->string('request_reference', 150)->nullable();
            $table->timestamps();

            $table->unique(['organisation_id', 'id'], 'uniq_financial_analysis_runs_organisation_id_id');;

            $table->index([
                'organisation_id',
                'borrower_id',
                'status',
            ], 'idx_financial_analysis_runs_organisation_id_borrower_id_status');;

            $table->index([
                'organisation_id',
                'borrower_id',
                'created_at',
            ], 'idx_financial_analysis_runs_organisation_id_borrower_id_created');;

            $table->index([
                'organisation_id',
                'statement_document_id',
            ], 'idx_financial_analysis_runs_organisation_id_statement_document');;

            $table->unique([
                'organisation_id',
                'request_reference',
            ], 'financial_analysis_runs_request_reference_unique');

            $table->foreign(
                ['organisation_id', 'borrower_id'],
                'financial_analysis_runs_org_borrower_fk'
            )->references(
                ['organisation_id', 'id']
            )->on('borrowers')
             ->restrictOnDelete();

            $table->foreign(
                ['organisation_id', 'statement_document_id'],
                'financial_analysis_runs_org_statement_fk'
            )->references(
                ['organisation_id', 'id']
            )->on('statement_documents')
             ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_analysis_runs');
    }
};
