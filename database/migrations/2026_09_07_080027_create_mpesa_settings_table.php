<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mpesa_settings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organisation_id')
                ->constrained('organisations')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('environment', 20)->default('sandbox');

            // Daraja credentials. Encrypt sensitive values at the application layer.
            $table->text('consumer_key');
            $table->text('consumer_secret');
            $table->text('passkey')->nullable();

            // M-Pesa routing identifiers.
            $table->string('business_shortcode', 30)->nullable();
            $table->string('head_office', 30)->nullable();
            $table->string('store_number', 30)->nullable();
            $table->string('till_number', 30)->nullable();

            // Optional legacy/partner credential retained for compatibility.
            $table->text('g2_password')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique([
                'organisation_id',
                'environment',
            ], 'uniq_mpesa_settings_organisation_id_environment');;

            $table->unique([
                'organisation_id',
                'id',
            ], 'uniq_mpesa_settings_organisation_id_id');;

            $table->index([
                'organisation_id',
                'is_active',
            ], 'idx_mpesa_settings_organisation_id_is_active');;
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mpesa_settings');
    }
};
