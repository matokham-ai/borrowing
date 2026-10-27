<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organisation_id')
                ->nullable()
                ->constrained('organisations')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('service', 100);
            $table->string('direction', 20)->default('outbound');
            $table->string('method', 10)->nullable();
            $table->string('endpoint', 500)->nullable();

            $table->string('request_reference', 150)->nullable();
            $table->string('external_reference', 150)->nullable();

            $table->unsignedSmallInteger('http_status')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();

            $table->string('status', 30)->default('pending');
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->text('error_message')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->unique(['organisation_id', 'id'], 'uniq_api_logs_organisation_id_id');;

            $table->index([
                'organisation_id',
                'service',
                'status',
                'created_at',
            ], 'idx_api_logs_organisation_id_service_status_created_at');;

            $table->index([
                'organisation_id',
                'request_reference',
            ], 'idx_api_logs_organisation_id_request_reference');;

            $table->index([
                'organisation_id',
                'external_reference',
            ], 'idx_api_logs_organisation_id_external_reference');;

            $table->foreign(
                ['organisation_id', 'user_id'],
                'api_logs_org_user_fk'
            )->references(
                ['organisation_id', 'id']
            )->on('users')
             ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_logs');
    }
};
