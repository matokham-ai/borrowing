<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('borrower_tiers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organisation_id')
                ->constrained('organisations')
                ->cascadeOnDelete();

            /*
             * Human/business identity of the tier.
             */
            $table->unsignedInteger('tier_number');
            $table->string('name');
            $table->text('description')->nullable();

            /*
             * Maximum principal a borrower at this tier may request.
             * Monetary values are stored in KES cents.
             * Example: KSh 1,000.00 = 100000.
             */
            $table->unsignedBigInteger('max_loan_amount_cents');

            /*
             * Optional minimum loan amount at this tier.
             */
            $table->unsignedBigInteger('min_loan_amount_cents')->default(0);

            /*
             * Maximum repayment period in days.
             */
            $table->unsignedSmallInteger('max_term_days');

            /*
             * Graduation requirements.
             */
            $table->unsignedInteger('minimum_successful_loans')->default(0);
            $table->unsignedInteger('minimum_on_time_loans')->default(0);
            $table->unsignedInteger('minimum_consecutive_on_time_loans')->default(0);

            /*
             * Minimum behaviour score required to enter/retain this tier.
             */
            $table->decimal('minimum_behaviour_score', 8, 4)->default(0);

            /*
             * Optional financial-appraisal requirement. Python may produce
             * a capacity/score; Laravel uses the configured rule here.
             */
            $table->decimal('minimum_financial_score', 8, 4)->nullable();

            /*
             * Prevent a borrower from holding more than the configured
             * exposure at this tier.
             */
            $table->unsignedBigInteger('max_total_exposure_cents')->nullable();

            /*
             * Tier lifecycle/configuration.
             */
            $table->boolean('is_entry_tier')->default(false);
            $table->boolean('is_active')->default(true)->index();

            /*
             * Version/configuration metadata. Rules should be auditable.
             */
            $table->unsignedInteger('version')->default(1);

            $table->timestamps();

            $table->unique(['organisation_id', 'tier_number'], 'uniq_borrower_tiers_organisation_id_tier_number');;
            $table->unique(['organisation_id', 'id'], 'uniq_borrower_tiers_organisation_id_id');;

            /*
             * One entry tier per organisation is enforced by application
             * logic/service validation. This migration remains portable
             * across MySQL versions.
             */
        });

        /*
         * Now that borrower_tiers exists, attach borrowers to their
         * current tier.
         */
        Schema::table('borrowers', function (Blueprint $table) {
            $table->foreign(
                ['organisation_id', 'current_tier_id'],
                'borrowers_org_current_tier_fk'
            )->references(
                ['organisation_id', 'id']
            )->on('borrower_tiers')
             ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('borrowers', function (Blueprint $table) {
            $table->dropForeign(['current_tier_id']);
        });

        Schema::dropIfExists('borrower_tiers');
    }
};
