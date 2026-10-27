<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();

            /*
             * Tenant ownership.
             */
            $table->foreignId('organisation_id')
                ->constrained('organisations')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            /*
             * Borrower who owes the loan.
             */
            $table->foreignId('borrower_id');

            /*
             * A loan is created from an approved application.
             *
             * One application can result in only one loan.
             */
            $table->foreignId('loan_application_id');

            /*
             * Product under which the loan was granted.
             */
            $table->foreignId('loan_product_id');

            /*
             * Exact tier rule used when the loan was approved.
             */
            $table->foreignId('loan_tier_rule_id')
                ->nullable();

            /*
             * =========================================================
             * IMMUTABLE LOAN TERMS
             * =========================================================
             *
             * These values represent the terms actually granted to
             * the borrower. They must not be recalculated from the
             * current loan product or tier rule later.
             */
            $table->unsignedBigInteger('principal_cents');

            /*
             * Interest rate actually applied to this loan.
             */
            $table->decimal('interest_rate', 10, 6)
                ->default(0);

            /*
             * Total interest charged on this loan.
             */
            $table->unsignedBigInteger('interest_cents')
                ->default(0);

            /*
             * Total fees charged on this loan.
             */
            $table->unsignedBigInteger('fees_cents')
                ->default(0);

            /*
             * Principal + interest + fees.
             */
            $table->unsignedBigInteger('total_payable_cents');

            /*
             * Repayment term and frequency actually granted.
             */
            $table->unsignedSmallInteger('term_days');

            $table->string('repayment_frequency', 30);

            /*
             * =========================================================
             * LOAN LIFECYCLE
             * =========================================================
             *
             * Suggested values:
             *
             * approved
             * disbursement_pending
             * active
             * overdue
             * completed
             * defaulted
             * written_off
             * cancelled
             */
            $table->string('status', 30)
                ->default('approved');

            /*
             * Lifecycle timestamps.
             */
            $table->timestamp('approved_at')
                ->nullable();

            $table->timestamp('disbursed_at')
                ->nullable();

            $table->timestamp('matures_at')
                ->nullable();

            $table->timestamp('completed_at')
                ->nullable();

            $table->timestamp('defaulted_at')
                ->nullable();

            $table->timestamp('written_off_at')
                ->nullable();

            $table->timestamps();

            /*
             * Required for tenant-safe composite foreign keys
             * from downstream tables such as loan_schedules.
             */
            $table->unique([
                'organisation_id',
                'id',
            ], 'uniq_loans_organisation_id_id');;

            /*
             * Borrower loan history and portfolio queries.
             */
            $table->index([
                'organisation_id',
                'borrower_id',
                'status',
            ], 'idx_loans_organisation_id_borrower_id_status');;

            /*
             * Portfolio/status/maturity queries.
             */
            $table->index([
                'organisation_id',
                'status',
                'matures_at',
            ], 'idx_loans_organisation_id_status_matures_at');;

            /*
             * =========================================================
             * TENANT-SAFE FOREIGN KEYS
             * =========================================================
             */

            /*
             * Borrower relationship.
             *
             * A borrower must belong to the same organisation as
             * the loan.
             *
             * Historical borrower records must not be deleted while
             * financial obligations reference them.
             */
            $table->foreign(
                ['organisation_id', 'borrower_id'],
                'loans_org_borrower_fk'
            )->references(
                ['organisation_id', 'id']
            )->on('borrowers')
             ->cascadeOnUpdate()
             ->restrictOnDelete();

            /*
             * Source application.
             *
             * The application is part of the historical lending
             * decision and must therefore be retained.
             */
            $table->foreign(
                ['organisation_id', 'loan_application_id'],
                'loans_org_application_fk'
            )->references(
                ['organisation_id', 'id']
            )->on('loan_applications')
             ->cascadeOnUpdate()
             ->restrictOnDelete();

            /*
             * Loan product.
             *
             * The product definition must remain available for
             * historical reference.
             */
            $table->foreign(
                ['organisation_id', 'loan_product_id'],
                'loans_org_product_fk'
            )->references(
                ['organisation_id', 'id']
            )->on('loan_products')
             ->cascadeOnUpdate()
             ->restrictOnDelete();

            /*
             * Exact tier rule used to approve this loan.
             *
             * This is historical lending evidence and should not
             * disappear merely because the rule is no longer active.
             */
            $table->foreign(
                ['organisation_id', 'loan_tier_rule_id'],
                'loans_org_tier_rule_fk'
            )->references(
                ['organisation_id', 'id']
            )->on('loan_tier_rules')
             ->cascadeOnUpdate()
             ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};

