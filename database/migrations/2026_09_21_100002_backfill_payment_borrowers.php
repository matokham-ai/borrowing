<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('loan_payments')
            ->whereNull('borrower_id')
            ->whereNotNull('loan_id')
            ->orderBy('id')
            ->chunkById(100, function ($payments): void {
                foreach ($payments as $payment) {
                    $borrowerId = DB::table('loans')
                        ->where('id', $payment->loan_id)
                        ->where('organisation_id', $payment->organisation_id)
                        ->value('borrower_id');

                    if ($borrowerId) {
                        DB::table('loan_payments')
                            ->where('id', $payment->id)
                            ->update(['borrower_id' => $borrowerId]);
                    }
                }
            });
    }

    public function down(): void
    {
        // The borrower link may have been populated after this migration.
    }
};
