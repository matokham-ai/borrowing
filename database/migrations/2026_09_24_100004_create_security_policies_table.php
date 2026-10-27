<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_policies', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organisation_id')
                ->constrained('organisations')
                ->cascadeOnDelete();

            // Password policy
            $table->unsignedTinyInteger('password_min_length')->default(8);
            $table->boolean('password_require_uppercase')->default(true);
            $table->boolean('password_require_number')->default(true);
            $table->boolean('password_require_symbol')->default(false);
            $table->unsignedSmallInteger('password_expiry_days')->default(90);  // 0 = never
            $table->unsignedTinyInteger('password_history_count')->default(5);  // prevent reuse

            // Session policy
            $table->unsignedSmallInteger('session_idle_minutes')->default(30);
            $table->unsignedTinyInteger('session_max_per_user')->default(3);   // 0 = unlimited
            $table->boolean('session_single_device')->default(false);

            // Login security
            $table->unsignedTinyInteger('max_login_attempts')->default(5);
            $table->unsignedSmallInteger('lockout_duration_minutes')->default(15);
            $table->boolean('two_factor_required')->default(false);
            $table->string('two_factor_method', 20)->default('totp');           // totp|sms|email

            // IP allow-list (JSON array of CIDR strings, null = any IP)
            $table->json('ip_allowlist')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('organisation_id', 'uniq_security_policies_org');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_policies');
    }
};
