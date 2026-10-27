<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('statement_documents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organisation_id')
                ->constrained('organisations')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('borrower_id')
                ->constrained('borrowers')
                ->restrictOnDelete();

            $table->string('document_type', 50)->default('mpesa_statement');
            $table->string('provider', 100)->default('mpesa');

            // Private object-storage reference; actual statement files
            // should not be stored in MySQL.
            $table->string('file_key', 500);
            $table->string('original_filename', 255)->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size_bytes')->nullable();
            $table->char('file_hash', 64)->nullable();

            $table->date('statement_period_start')->nullable();
            $table->date('statement_period_end')->nullable();

            $table->string('processing_status', 30)->default('uploaded');
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamp('processed_at')->nullable();

            $table->unsignedInteger('transaction_count')->default(0);
            $table->json('document_metadata')->nullable();
            $table->json('processing_snapshot')->nullable();

            $table->timestamps();

            $table->unique(['organisation_id', 'id'], 'uniq_statement_documents_organisation_id_id');;

            $table->index([
                'organisation_id',
                'borrower_id',
                'processing_status',
            ], 'idx_statement_documents_organisation_id_borrower_id_processing');;

            $table->index([
                'organisation_id',
                'borrower_id',
                'statement_period_start',
                'statement_period_end',
            ], 'idx_statement_documents_organisation_id_borrower_id_statement_p');;

            $table->index([
                'organisation_id',
                'file_hash',
            ], 'idx_statement_documents_organisation_id_file_hash');;

            $table->foreign(
                ['organisation_id', 'borrower_id'],
                'statement_documents_org_borrower_fk'
            )->references(
                ['organisation_id', 'id']
            )->on('borrowers')
             ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statement_documents');
    }
};
