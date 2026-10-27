<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tier_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organisation_id')
                ->constrained('organisations')
                ->cascadeOnDelete();

            $table->foreignId('borrower_id')
                ->constrained('borrowers')
                ->restrictOnDelete();

            $table->unsignedBigInteger('from_tier_id')->nullable();
            $table->unsignedBigInteger('to_tier_id');

            $table->string('change_type', 30); // initial, graduation, demotion, manual
            $table->string('reason', 255)->nullable();

            // Evidence used for the decision.
            $table->decimal('behaviour_score', 8, 4)->nullable();
            $table->decimal('financial_score', 8, 4)->nullable();
            $table->unsignedInteger('successful_loans_count')->nullable();
            $table->unsignedInteger('on_time_loans_count')->nullable();
            $table->unsignedInteger('consecutive_on_time_loans')->nullable();

            $table->string('rule_version', 100)->nullable();
            $table->json('decision_snapshot')->nullable();

            $table->timestamp('effective_at');
            $table->timestamps();

            $table->index(['organisation_id', 'borrower_id', 'effective_at'], 'idx_tier_histories_organisation_id_borrower_id_effective_at');;
            $table->index(['organisation_id', 'to_tier_id'], 'idx_tier_histories_organisation_id_to_tier_id');;

            $table->foreign(
                ['organisation_id', 'borrower_id'],
                'tier_histories_org_borrower_fk'
            )->references(
                ['organisation_id', 'id']
            )->on('borrowers')
             ->restrictOnDelete();

            $table->foreign(
                ['organisation_id', 'from_tier_id'],
                'tier_histories_org_from_tier_fk'
            )->references(
                ['organisation_id', 'id']
            )->on('borrower_tiers')
             ->restrictOnDelete();

            $table->foreign(
                ['organisation_id', 'to_tier_id'],
                'tier_histories_org_to_tier_fk'
            )->references(
                ['organisation_id', 'id']
            )->on('borrower_tiers')
             ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tier_histories');
    }
};
