<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
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

            $table->string('action', 100);
            $table->string('auditable_type', 150)->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();

            $table->string('description', 500)->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('context')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();

            $table->unique(['organisation_id', 'id'], 'uniq_audit_logs_organisation_id_id');;

            $table->index([
                'organisation_id',
                'auditable_type',
                'auditable_id',
            ], 'idx_audit_logs_organisation_id_auditable_type_auditable_id');;

            $table->index([
                'organisation_id',
                'user_id',
                'occurred_at',
            ], 'idx_audit_logs_organisation_id_user_id_occurred_at');;

            $table->index([
                'organisation_id',
                'action',
                'occurred_at',
            ], 'idx_audit_logs_organisation_id_action_occurred_at');;

            $table->foreign(
                ['organisation_id', 'user_id'],
                'audit_logs_org_user_fk'
            )->references(
                ['organisation_id', 'id']
            )->on('users')
             ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
