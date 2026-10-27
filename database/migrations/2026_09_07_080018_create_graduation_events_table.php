<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('graduation_events', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organisation_id')
                ->constrained('organisations')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->unsignedBigInteger('borrower_id');
            $table->unsignedBigInteger('from_tier_id')->nullable();
            $table->unsignedBigInteger('to_tier_id');
            $table->unsignedBigInteger('loan_id')->nullable();

            $table->string('event_type', 40)->default('graduated');

            $table->decimal('behaviour_score', 8, 4)->nullable();
            $table->decimal('financial_score', 8, 4)->nullable();

            // Exact rule/configuration that justified the decision.
            $table->unsignedBigInteger('loan_tier_rule_id')->nullable();
            $table->string('tier_rule_version', 100)->nullable();

            $table->text('reason')->nullable();
            $table->json('decision_snapshot')->nullable();

            $table->timestamp('occurred_at');

            $table->timestamps();

            $table->unique(['organisation_id', 'id'], 'uniq_graduation_events_organisation_id_id');;

            $table->index([
                'organisation_id',
                'borrower_id',
                'occurred_at',
            ], 'idx_graduation_events_organisation_id_borrower_id_occurred_at');;

            $table->index([
                'organisation_id',
                'to_tier_id',
                'occurred_at',
            ], 'idx_graduation_events_organisation_id_to_tier_id_occurred_at');;

            $table->foreign(
                ['organisation_id', 'borrower_id'],
                'graduation_events_org_borrower_fk'
            )->references(
                ['organisation_id', 'id']
            )->on('borrowers')
             ->cascadeOnUpdate()
             ->restrictOnDelete();

            $table->foreign(
                ['organisation_id', 'from_tier_id'],
                'graduation_events_org_from_tier_fk'
            )->references(
                ['organisation_id', 'id']
            )->on('borrower_tiers')
             ->cascadeOnUpdate()
             ->restrictOnDelete();

            $table->foreign(
                ['organisation_id', 'to_tier_id'],
                'graduation_events_org_to_tier_fk'
            )->references(
                ['organisation_id', 'id']
            )->on('borrower_tiers')
             ->cascadeOnUpdate()
             ->restrictOnDelete();

            $table->foreign(
                ['organisation_id', 'loan_id'],
                'graduation_events_org_loan_fk'
            )->references(
                ['organisation_id', 'id']
            )->on('loans')
             ->cascadeOnUpdate()
             ->restrictOnDelete();

            $table->foreign(
                ['organisation_id', 'loan_tier_rule_id'],
                'graduation_events_org_rule_fk'
            )->references(
                ['organisation_id', 'id']
            )->on('loan_tier_rules')
             ->cascadeOnUpdate()
             ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('graduation_events');
    }
};
