<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_classifications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organisation_id')
                ->constrained('organisations')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('statement_document_id')
                ->constrained('statement_documents')
                ->restrictOnDelete();

            $table->foreignId('borrower_id')
                ->constrained('borrowers')
                ->restrictOnDelete();

            // Provider/source transaction identity.
            $table->string('source_transaction_id', 150)->nullable();
            $table->timestamp('transaction_at')->nullable();

            $table->string('transaction_type', 50)->nullable();
            $table->string('description', 500)->nullable();
            $table->string('counterparty', 255)->nullable();

            // Signed amount: positive = money in, negative = money out.
            $table->bigInteger('amount_cents');
            $table->string('currency', 3)->default('KES');

            $table->bigInteger('balance_after_cents')->nullable();

            // Classification produced by the financial-analysis pipeline.
            $table->string('classification', 100);
            $table->decimal('classification_confidence', 8, 6)->nullable();
            $table->string('classifier_version', 100)->nullable();

            $table->boolean('is_income')->default(false);
            $table->boolean('is_expense')->default(false);
            $table->boolean('is_transfer')->default(false);
            $table->boolean('is_loan_related')->default(false);
            $table->boolean('is_repayment')->default(false);

            $table->json('classification_snapshot')->nullable();

            $table->timestamps();

            $table->unique(['organisation_id', 'id'], 'uniq_transaction_classifications_organisation_id_id');;

            $table->unique([
                'organisation_id',
                'statement_document_id',
                'source_transaction_id',
            ], 'transaction_classifications_source_unique');

            $table->index([
                'organisation_id',
                'borrower_id',
                'transaction_at',
            ], 'uniq_transaction_classifications_organisation_id_statement_docu');;

            $table->index([
                'organisation_id',
                'classification',
                'transaction_at',
            ], 'idx_transaction_classifications_organisation_id_classification');;

            $table->index([
                'organisation_id',
                'statement_document_id',
            ], 'idx_transaction_classifications_organisation_id_statement_docum');;

            $table->foreign(
                ['organisation_id', 'statement_document_id'],
                'transaction_classifications_org_statement_fk'
            )->references(
                ['organisation_id', 'id']
            )->on('statement_documents')
             ->restrictOnDelete();

            $table->foreign(
                ['organisation_id', 'borrower_id'],
                'transaction_classifications_org_borrower_fk'
            )->references(
                ['organisation_id', 'id']
            )->on('borrowers')
             ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_classifications');
    }
};
