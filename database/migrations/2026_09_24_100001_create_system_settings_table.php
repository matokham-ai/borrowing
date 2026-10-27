<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organisation_id')
                ->constrained('organisations')
                ->cascadeOnDelete();

            // Grouping / namespacing
            $table->string('group', 60)->default('general')->index();  // e.g. general, loans, payments
            $table->string('key', 120);
            $table->text('value')->nullable();
            $table->string('type', 20)->default('string');   // string|boolean|integer|json|select
            $table->string('label', 255)->nullable();
            $table->text('description')->nullable();
            $table->json('options')->nullable();              // for select / radio types
            $table->boolean('is_public')->default(false);    // expose to front-end scripts
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();

            $table->unique(['organisation_id', 'group', 'key'], 'uniq_system_settings_org_group_key');
            $table->index(['organisation_id', 'group'], 'idx_system_settings_org_group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
