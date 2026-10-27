<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mpesa_webhook_events', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organisation_id')
                ->constrained('organisations')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->unsignedBigInteger('mpesa_transaction_id')->nullable();

            $table->string('event_type', 50);
            $table->string('idempotency_key', 200);

            $table->json('payload');

            $table->string('source_ip_address', 45)->nullable();

            $table->string('processing_status', 30)->default('received');
            // received, queued, processed, ignored, failed

            $table->text('processing_error')->nullable();

            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();

            $table->timestamps();

            $table->unique([
                'organisation_id',
                'idempotency_key',
            ], 'uniq_mpesa_webhook_events_organisation_id_idempotency_key');;

            $table->unique([
                'organisation_id',
                'id',
            ], 'uniq_mpesa_webhook_events_organisation_id_id');;

            $table->index([
                'organisation_id',
                'event_type',
                'processing_status',
            ], 'idx_mpesa_webhook_events_organisation_id_event_type_processing');;

            $table->index([
                'organisation_id',
                'received_at',
            ], 'idx_mpesa_webhook_events_organisation_id_received_at');;

            $table->foreign(
                ['organisation_id', 'mpesa_transaction_id'],
                'mpesa_webhooks_org_transaction_fk'
            )->references(
                ['organisation_id', 'id']
            )->on('mpesa_transactions')
             ->cascadeOnUpdate()
             ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mpesa_webhook_events');
    }
};
