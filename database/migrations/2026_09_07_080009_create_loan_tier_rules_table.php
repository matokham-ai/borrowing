
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_tier_rules', function (Blueprint $table) {
            $table->id();

            /*
             * Tenant ownership.
             */
            $table->foreignId('organisation_id')
                ->constrained('organisations')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            /*
             * The loan product this rule applies to.
             *
             * A borrower tier may have different borrowing terms
             * depending on the loan product.
             */
            $table->foreignId('loan_product_id');

            /*
             * The borrower graduation tier to which this rule applies.
             */
            $table->unsignedBigInteger('borrower_tier_id');

            /*
             * Product-specific borrowing limits available at this tier.
             */
            $table->unsignedBigInteger('maximum_amount_cents');
            $table->unsignedBigInteger('minimum_amount_cents')->default(0);
            $table->unsignedSmallInteger('maximum_term_days');

            /*
             * Maximum total outstanding exposure permitted for
             * this borrower under this product/tier rule.
             */
            $table->unsignedBigInteger('maximum_total_exposure_cents')
                ->nullable();

            /*
             * Behavioural evidence required before the borrower
             * can access this product at this tier.
             */
            $table->unsignedInteger('minimum_successful_loans')
                ->default(0);

            $table->unsignedInteger('minimum_on_time_loans')
                ->default(0);

            $table->unsignedInteger('minimum_consecutive_on_time_loans')
                ->default(0);

            /*
             * Minimum scores required by the appraisal engine.
             */
            $table->decimal('minimum_behaviour_score', 8, 4)
                ->default(0);

            $table->decimal('minimum_financial_score', 8, 4)
                ->nullable();

            /*
             * Rule lifecycle and versioning.
             *
             * Once a rule has been used for an application/loan,
             * its historical version should be preserved.
             */
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('version')->default(1);

            $table->timestamps();

            /*
             * A product can have one specific rule version for
             * each borrower tier.
             */
            $table->unique([
                'organisation_id',
                'loan_product_id',
                'borrower_tier_id',
                'version',
            ], 'uniq_loan_tier_rules_organisation_id_loan_product_id_borrower_t');;

            /*
             * Required so this table can participate in
             * tenant-safe composite foreign keys.
             */
            $table->unique([
                'organisation_id',
                'id',
            ], 'uniq_loan_tier_rules_organisation_id_id');;

            /*
             * Common lookup:
             * find the active rule for a product and borrower tier.
             */
            $table->index([
                'organisation_id',
                'loan_product_id',
                'borrower_tier_id',
                'is_active',
            ], 'idx_loan_tier_rules_organisation_id_loan_product_id_borrower_ti');;

            /*
             * Product relationship.
             *
             * A product must not be physically deleted while
             * historical lending rules depend on it.
             */
            $table->foreign(
                ['organisation_id', 'loan_product_id'],
                'loan_tier_rules_org_product_fk'
            )->references(
                ['organisation_id', 'id']
            )->on('loan_products')
             ->cascadeOnUpdate()
             ->restrictOnDelete();

            /*
             * Borrower graduation-tier relationship.
             *
             * Historical lending rules must remain valid even
             * when a tier is no longer active.
             */
            $table->foreign(
                ['organisation_id', 'borrower_tier_id'],
                'loan_tier_rules_org_borrower_tier_fk'
            )->references(
                ['organisation_id', 'id']
            )->on('borrower_tiers')
             ->cascadeOnUpdate()
             ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_tier_rules');
    }
};

