<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('behaviour_scores', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organisation_id')
                ->constrained('organisations')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->unsignedBigInteger('borrower_id');

            // Snapshot of the score calculated at this point in time.
            $table->decimal('score', 8, 4);
            $table->decimal('repayment_score', 8, 4)->nullable();
            $table->decimal('timeliness_score', 8, 4)->nullable();
            $table->decimal('consistency_score', 8, 4)->nullable();
            $table->decimal('discipline_score', 8, 4)->nullable();

            $table->string('model_version', 100)->nullable();

            // Number of relevant behavioural observations used.
            $table->unsignedInteger('observation_count')->default(0);

            // Reproducibility/audit snapshot of the inputs and calculation.
            $table->json('score_snapshot')->nullable();

            $table->timestamp('calculated_at');

            $table->timestamps();

            $table->unique(['organisation_id', 'id'], 'uniq_behaviour_scores_organisation_id_id');;

            $table->index([
                'organisation_id',
                'borrower_id',
                'calculated_at',
            ], 'idx_behaviour_scores_organisation_id_borrower_id_calculated_at');;

            $table->index([
                'organisation_id',
                'borrower_id',
                'score',
            ], 'idx_behaviour_scores_organisation_id_borrower_id_score');;

            $table->foreign(
                ['organisation_id', 'borrower_id'],
                'behaviour_scores_org_borrower_fk'
            )->references(
                ['organisation_id', 'id']
            )->on('borrowers')
             ->cascadeOnUpdate()
             ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('behaviour_scores');
    }
};
