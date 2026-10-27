<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_applications', function (Blueprint $table) {
            $table->id();

            /*
             * Tenant ownership.
             */
            $table->foreignId('organisation_id')
                ->constrained('organisations')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            /*
             * Applicant.
             */
            $table->foreignId('borrower_id');

            /*
             * Loan product requested.
             */
            $table->foreignId('loan_product_id');

            /*
             * Specific product/tier rule used when assessing
             * the application.
             *
             * Nullable because an application may exist before
             * the applicable tier rule has been resolved.
             */
            $table->foreignId('loan_tier_rule_id')
                ->nullable();

            /*
             * Original request.
             */
            $table->unsignedBigInteger('requested_amount_cents');
            $table->unsignedSmallInteger('requested_term_days');

            /*
             * Snapshot of the appraisal results at the time
             * the application was assessed.
             *
             * These are intentionally stored on the application
             * because the underlying financial profile and
             * scoring models may change later.
             */
            $table->decimal('financial_score', 8, 4)
                ->nullable();

            $table->decimal('behaviour_score', 8, 4)
                ->nullable();

            /*
             * Approved terms.
             *
             * These may differ from what the borrower requested.
             */
            $table->unsignedBigInteger('approved_amount_cents')
                ->nullable();

            $table->unsignedSmallInteger('approved_term_days')
                ->nullable();

            /*
             * Application lifecycle.
             *
             * Suggested values:
             * pending
             * under_review
             * approved
             * rejected
             * withdrawn
             * expired
             */
            $table->string('status', 30)
                ->default('pending');

            /*
             * Final lending decision.
             *
             * Suggested values:
             * approved
             * rejected
             * referred
             */
            $table->string('decision', 30)
                ->nullable();

            /*
             * Human-readable explanation of the decision.
             */
            $table->text('decision_reason')
                ->nullable();

            /*
             * Versioning of the financial appraisal model
             * and tier rule used for this decision.
             *
             * These make the decision reproducible/auditable.
             */
            $table->string('appraisal_model_version', 100)
                ->nullable();

            $table->string('tier_rule_version', 100)
                ->nullable();

            /*
             * Complete decision-time snapshot.
             *
             * This can contain the inputs, thresholds,
             * calculated scores, rule results and other
             * evidence used to arrive at the decision.
             */
            $table->json('decision_snapshot')
                ->nullable();

            /*
             * Application lifecycle timestamps.
             */
            $table->timestamp('submitted_at')
                ->nullable();

            $table->timestamp('decided_at')
                ->nullable();

            $table->timestamp('expired_at')
                ->nullable();

            $table->timestamps();

            /*
             * Required for tenant-safe composite foreign keys
             * from downstream tables such as loans.
             */
            $table->unique([
                'organisation_id',
                'id',
            ], 'uniq_loan_applications_organisation_id_id');;

            /*
             * Common borrower/application queries.
             */
            $table->index([
                'organisation_id',
                'borrower_id',
                'status',
            ], 'idx_loan_applications_organisation_id_borrower_id_status');;

            /*
             * Product/application queries.
             */
            $table->index([
                'organisation_id',
                'loan_product_id',
                'status',
            ], 'idx_loan_applications_organisation_id_loan_product_id_status');;

            /*
             * Queue/review/dashboard queries.
             */
            $table->index([
                'organisation_id',
                'status',
                'created_at',
            ], 'idx_loan_applications_organisation_id_status_created_at');;

            /*
             * Tenant-safe borrower relationship.
             *
             * This prevents an application belonging to one
             * organisation from referencing a borrower belonging
             * to another organisation.
             */
            $table->foreign(
                ['organisation_id', 'borrower_id'],
                'loan_applications_org_borrower_fk'
            )->references(
                ['organisation_id', 'id']
            )->on('borrowers')
             ->cascadeOnUpdate()
             ->restrictOnDelete();

            /*
             * Tenant-safe product relationship.
             *
             * A product that has been used in an application
             * should not be physically deleted.
             */
            $table->foreign(
                ['organisation_id', 'loan_product_id'],
                'loan_applications_org_product_fk'
            )->references(
                ['organisation_id', 'id']
            )->on('loan_products')
             ->cascadeOnUpdate()
             ->restrictOnDelete();

            /*
             * Tenant-safe tier-rule relationship.
             *
             * The rule is part of the historical decision context,
             * so it must not disappear while the application
             * references it.
             */
            $table->foreign(
                ['organisation_id', 'loan_tier_rule_id'],
                'loan_applications_org_tier_rule_fk'
            )->references(
                ['organisation_id', 'id']
            )->on('loan_tier_rules')
             ->cascadeOnUpdate()
             ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_applications');
    }
};
