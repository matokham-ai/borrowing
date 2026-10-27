<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integrations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organisation_id')
                ->constrained('organisations')
                ->cascadeOnDelete();

            $table->string('provider', 60);            // e.g. mpesa, africas_talking, sendgrid
            $table->string('type', 40);                // payment | sms | email | kyc | webhook
            $table->string('label', 120);              // human-readable display name
            $table->string('environment', 20)->default('sandbox');  // sandbox | production
            $table->json('credentials')->nullable();   // encrypted at app layer before storage
            $table->json('config')->nullable();        // non-sensitive config (callback URLs etc.)
            $table->string('status', 20)->default('inactive'); // active|inactive|error
            $table->timestamp('last_tested_at')->nullable();
            $table->text('last_error')->nullable();
            $table->boolean('is_active')->default(false);

            $table->timestamps();

            $table->unique(['organisation_id', 'provider', 'environment'], 'uniq_integrations_org_provider_env');
            $table->index(['organisation_id', 'type'], 'idx_integrations_org_type');
            $table->index(['organisation_id', 'status'], 'idx_integrations_org_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integrations');
    }
};
