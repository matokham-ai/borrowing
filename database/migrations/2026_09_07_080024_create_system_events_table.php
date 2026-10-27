<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_events', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organisation_id')
                ->nullable()
                ->constrained('organisations')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->string('event_type', 150);
            $table->string('event_version', 30)->default('1');
            $table->string('source', 100)->default('laravel');

            $table->string('aggregate_type', 150)->nullable();
            $table->unsignedBigInteger('aggregate_id')->nullable();

            $table->string('event_id', 150);
            $table->string('idempotency_key', 150)->nullable();

            $table->json('payload')->nullable();

            $table->string('status', 30)->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();

            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['organisation_id', 'id'], 'uniq_system_events_organisation_id_id');;

            $table->unique(['organisation_id', 'event_id'],
                'system_events_org_event_id_unique'
            );

            $table->unique(
                ['organisation_id', 'idempotency_key'],
                'system_events_org_idempotency_unique'
            );

            $table->index([
                'organisation_id',
                'event_type',
                'status',
                'occurred_at',
            ], 'uniq_system_events_organisation_id_event_id_system_events_org_e');;

            $table->index([
                'organisation_id',
                'aggregate_type',
                'aggregate_id',
            ], 'idx_system_events_organisation_id_aggregate_type_aggregate_id');;

            $table->index([
                'organisation_id',
                'status',
                'created_at',
            ], 'idx_system_events_organisation_id_status_created_at');;
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_events');
    }
};
