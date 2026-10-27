<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kyc_records', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organisation_id')
                ->constrained('organisations')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('borrower_id')
                ->constrained('borrowers')
                ->restrictOnDelete();

            $table->string('document_type', 50);
            $table->string('document_number', 100)->nullable();
            $table->string('document_country_code', 2)->default('KE');

            $table->string('full_name', 255)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('verification_status', 30)->default('pending');

            $table->string('verification_provider', 100)->nullable();
            $table->string('verification_reference', 150)->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_notes')->nullable();

            // Store a safe reference to the evidence rather than sensitive
            // document contents in the relational database.
            $table->string('document_file_key', 500)->nullable();
            $table->json('verification_snapshot')->nullable();

            $table->boolean('is_current')->default(true);
            $table->timestamps();

            $table->unique(['organisation_id', 'id'], 'uniq_kyc_records_organisation_id_id');;

            $table->index([
                'organisation_id',
                'borrower_id',
                'document_type',
                'verification_status',
            ], 'idx_kyc_records_organisation_id_borrower_id_document_type_verif');;

            $table->index([
                'organisation_id',
                'borrower_id',
                'is_current',
            ], 'idx_kyc_records_organisation_id_borrower_id_is_current');;

            $table->foreign(
                ['organisation_id', 'borrower_id'],
                'kyc_records_org_borrower_fk'
            )->references(
                ['organisation_id', 'id']
            )->on('borrowers')
             ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kyc_records');
    }
};
