<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'bronze' => 0.40,
            'silver' => 0.60,
            'gold' => 0.80,
            'platinum' => 0.90,
        ] as $name => $score) {
            DB::table('borrower_tiers')
                ->whereRaw('LOWER(name) = ?', [$name])
                ->whereNull('minimum_financial_score')
                ->update(['minimum_financial_score' => $score]);
        }
    }

    public function down(): void
    {
        // Keep these business thresholds if the migration is rolled back.
    }
};
