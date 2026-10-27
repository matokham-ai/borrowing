<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_profiles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organisation_id')
                ->constrained('organisations')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('borrower_id')
                ->constrained('borrowers')
                ->restrictOnDelete();

            $table->foreignId('financial_analysis_run_id')
                ->nullable()
                ->constrained('financial_analysis_runs')
                ->restrictOnDelete();

            // Aggregated financial intelligence. Values are snapshots,
            // not the authoritative lending ledger.
            $table->string('currency', 3)->default('KES');

            $table->bigInteger('average_monthly_income_cents')->nullable();
            $table->bigInteger('average_monthly_expense_cents')->nullable();
            $table->bigInteger('average_monthly_net_cashflow_cents')->nullable();

            $table->bigInteger('average_monthly_business_income_cents')->nullable();
            $table->bigInteger('average_monthly_salary_income_cents')->nullable();
            $table->bigInteger('average_monthly_other_income_cents')->nullable();

            $table->bigInteger('average_monthly_debt_service_cents')->nullable();
            $table->bigInteger('average_monthly_discretionary_cashflow_cents')->nullable();

            $table->decimal('income_stability_score', 8, 4)->nullable();
            $table->decimal('cashflow_score', 8, 4)->nullable();
            $table->decimal('financial_score', 8, 4)->nullable();

            $table->decimal('income_volatility', 12, 6)->nullable();
            $table->decimal('expense_volatility', 12, 6)->nullable();
            $table->decimal('debt_service_ratio', 12, 6)->nullable();

            $table->unsignedInteger('analysis_months')->default(0);
            $table->unsignedInteger('transaction_count')->default(0);

            $table->string('model_version', 100)->nullable();
            $table->json('profile_snapshot')->nullable();

            $table->boolean('is_current')->default(true);
            $table->timestamp('calculated_at')->nullable();

            $table->timestamps();

            $table->unique(['organisation_id', 'id'], 'uniq_financial_profiles_organisation_id_id');;

            $table->index([
                'organisation_id',
                'borrower_id',
                'is_current',
            ], 'idx_financial_profiles_organisation_id_borrower_id_is_current');;

            $table->index([
                'organisation_id',
                'financial_analysis_run_id',
            ], 'idx_financial_profiles_organisation_id_financial_analysis_run_i');;

            $table->foreign(
                ['organisation_id', 'borrower_id'],
                'financial_profiles_org_borrower_fk'
            )->references(
                ['organisation_id', 'id']
            )->on('borrowers')
             ->restrictOnDelete();

            $table->foreign(
                ['organisation_id', 'financial_analysis_run_id'],
                'financial_profiles_org_analysis_run_fk'
            )->references(
                ['organisation_id', 'id']
            )->on('financial_analysis_runs')
             ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_profiles');
    }
};
