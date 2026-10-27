<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('organisations', function (Blueprint $table) {
            // Additional identity fields
            $table->string('trading_name')->nullable();
            $table->string('organisation_type')->nullable()->comment('Microfinance Institution, Bank, SACCO, etc.');
            $table->string('tax_pin')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();

            // Address fields
            $table->string('county')->nullable();
            $table->string('town')->nullable();
            $table->text('physical_address')->nullable();

            // Branding fields
            $table->string('logo_path')->nullable();
            $table->string('primary_brand_color')->nullable()->default('#1F4B99');
            $table->string('secondary_brand_color')->nullable();
            $table->boolean('use_org_branding')->default(true);

            // Lending configuration defaults
            $table->integer('default_loan_term')->default(30)->comment('Default loan term in days');
            $table->string('default_repayment_frequency')->default('monthly')->comment('daily, weekly, monthly');
            $table->string('interest_calculation_method')->default('reducing_balance')->comment('reducing_balance, flat_rate');
            $table->boolean('late_payment_handling')->default(true);
            $table->integer('grace_period')->default(3)->comment('Grace period in days');
            $table->integer('maximum_active_loans')->default(3);

            // General configuration
            $table->string('date_format')->default('DD/MM/YYYY');
            $table->string('financial_year_start')->default('January');
            $table->string('financial_year_end')->default('December');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organisations', function (Blueprint $table) {
            // Additional identity fields
            $table->dropColumn(['trading_name', 'organisation_type', 'tax_pin', 'phone', 'email', 'website']);

            // Address fields
            $table->dropColumn(['county', 'town', 'physical_address']);

            // Branding fields
            $table->dropColumn(['logo_path', 'primary_brand_color', 'secondary_brand_color', 'use_org_branding']);

            // Lending configuration defaults
            $table->dropColumn(['default_loan_term', 'default_repayment_frequency', 'interest_calculation_method', 'late_payment_handling', 'grace_period', 'maximum_active_loans']);

            // General configuration
            $table->dropColumn(['date_format', 'financial_year_start', 'financial_year_end']);
        });
    }
};
