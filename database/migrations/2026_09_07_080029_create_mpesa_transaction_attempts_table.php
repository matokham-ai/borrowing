<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mpesa_transaction_attempts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organisation_id')
                ->constrained('organisations')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->unsignedBigInteger('mpesa_transaction_id');

            $table->unsignedInteger('attempt_number');

            $table->string('status', 30)->default('submitted');

            $table->string('request_reference', 200)->nullable();

            $table->string('response_code', 50)->nullable();
            $table->text('response_description')->nullable();

            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->unique([
                'organisation_id',
                'mpesa_transaction_id',
                'attempt_number',
            ], 'uniq_mpesa_transaction_attempts_organisation_id_mpesa_transacti');;

            $table->unique([
                'organisation_id',
                'id',
            ], 'uniq_mpesa_transaction_attempts_organisation_id_id');;

            $table->index([
                'organisation_id',
                'status',
                'submitted_at',
            ], 'idx_mpesa_transaction_attempts_organisation_id_status_submitted');;

            $table->foreign(
                ['organisation_id', 'mpesa_transaction_id'],
                'mpesa_attempts_org_transaction_fk'
            )->references(
                ['organisation_id', 'id']
            )->on('mpesa_transactions')
             ->cascadeOnUpdate()
             ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mpesa_transaction_attempts');
    }
};
