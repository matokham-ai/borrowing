<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
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

            $table->foreignId('borrower_id')
                ->nullable()
                ->constrained('borrowers')
                ->nullOnDelete();

            $table->string('channel', 30)->default('in_app');
            $table->string('notification_type', 100);
            $table->string('title', 255);
            $table->text('message');

            $table->json('data')->nullable();

            $table->timestamp('read_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();

            $table->string('status', 30)->default('pending');

            $table->string('reference_type', 150)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();

            $table->timestamps();

            $table->unique(['organisation_id', 'id'], 'uniq_notifications_organisation_id_id');;

            $table->index([
                'organisation_id',
                'user_id',
                'status',
                'created_at',
            ], 'idx_notifications_organisation_id_user_id_status_created_at');;

            $table->index([
                'organisation_id',
                'borrower_id',
                'status',
                'created_at',
            ], 'idx_notifications_organisation_id_borrower_id_status_created_at');;

            $table->index([
                'organisation_id',
                'reference_type',
                'reference_id',
            ], 'idx_notifications_organisation_id_reference_type_reference_id');;

            $table->foreign(
                ['organisation_id', 'user_id'],
                'notifications_org_user_fk'
            )->references(
                ['organisation_id', 'id']
            )->on('users')
             ->nullOnDelete();

            $table->foreign(
                ['organisation_id', 'borrower_id'],
                'notifications_org_borrower_fk'
            )->references(
                ['organisation_id', 'id']
            )->on('borrowers')
             ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
