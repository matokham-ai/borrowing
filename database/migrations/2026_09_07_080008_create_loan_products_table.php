<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organisation_id')->constrained('organisations')
                ->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('minimum_amount_cents');
            $table->unsignedBigInteger('maximum_amount_cents');
            $table->unsignedInteger('minimum_term_days');
            $table->unsignedInteger('maximum_term_days');
            $table->string('interest_type', 30)->default('flat');
            $table->decimal('interest_rate', 10, 4);
            $table->string('fee_type', 30)->default('none');
            $table->decimal('fee_rate', 10, 4)->default(0);
            $table->unsignedBigInteger('fixed_fee_cents')->default(0);
            $table->string('repayment_frequency', 30)->default('daily');
            $table->unsignedInteger('grace_period_days')->default(0);
            $table->boolean('requires_appraisal')->default(true);
            $table->boolean('requires_statement')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->unique(['organisation_id', 'code'], 'uniq_loan_products_organisation_id_code');;
            $table->unique(['organisation_id', 'id'], 'uniq_loan_products_organisation_id_id');;
            $table->index(['organisation_id', 'is_active'], 'idx_loan_products_organisation_id_is_active');;
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_products');
    }
};
