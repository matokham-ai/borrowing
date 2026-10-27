<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organisations', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('slug')->unique();

            // Legal/business identity
            $table->string('company_reg_number')->nullable()->unique();

            // Organisation operating settings
            $table->string('country_code', 2)->default('KE');
            $table->string('currency', 3)->default('KES');
            $table->string('timezone')->default('Africa/Nairobi');

            $table->boolean('is_active')->default(true)->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organisations');
    }
};
