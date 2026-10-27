<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organisation_id')
                ->constrained('organisations')
                ->cascadeOnDelete();

            $table->string('name', 120);
            $table->string('event', 120);                    // e.g. loan_approved, payment_received
            $table->string('channel', 20);                   // sms | email | in_app
            $table->string('subject', 255)->nullable();      // email subject line
            $table->text('body');
            $table->json('variables')->nullable();           // documented placeholders e.g. {{borrower_name}}
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_tested_at')->nullable();

            $table->timestamps();

            $table->unique(['organisation_id', 'event', 'channel'], 'uniq_notif_templates_org_event_channel');
            $table->index(['organisation_id', 'channel'], 'idx_notif_templates_org_channel');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
