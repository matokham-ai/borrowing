<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mpesa_transactions', function (Blueprint $table) {
            // Safaricom sends OrgAccountBalance (string, e.g. "12345.00") in C2B
            // confirmations. We store it as integer cents alongside the transaction.
            $table->unsignedBigInteger('org_account_balance_cents')
                  ->nullable()
                  ->after('amount_cents')
                  ->comment('Paybill/till working-account balance at time of transaction (C2B only)');
        });

        // Backfill from provider_payload for existing C2B transactions.
        // JSON_UNQUOTE is MySQL-only; SQLite's json_extract already returns plain values.
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("
                UPDATE mpesa_transactions
                SET org_account_balance_cents = CAST(
                    CAST(JSON_UNQUOTE(JSON_EXTRACT(provider_payload, '$.OrgAccountBalance')) AS DECIMAL(18,2))
                    * 100 AS UNSIGNED
                )
                WHERE transaction_type IN ('c2b_confirmation', 'c2b')
                  AND provider_payload IS NOT NULL
                  AND JSON_UNQUOTE(JSON_EXTRACT(provider_payload, '$.OrgAccountBalance')) IS NOT NULL
                  AND JSON_UNQUOTE(JSON_EXTRACT(provider_payload, '$.OrgAccountBalance')) != 'null'
            ");
        } elseif ($driver === 'sqlite') {
            // SQLite: json_extract returns unquoted scalars directly; CAST to REAL then * 100
            DB::statement("
                UPDATE mpesa_transactions
                SET org_account_balance_cents = CAST(
                    CAST(json_extract(provider_payload, '$.OrgAccountBalance') AS REAL)
                    * 100 AS INTEGER
                )
                WHERE transaction_type IN ('c2b_confirmation', 'c2b')
                  AND provider_payload IS NOT NULL
                  AND json_extract(provider_payload, '$.OrgAccountBalance') IS NOT NULL
                  AND json_extract(provider_payload, '$.OrgAccountBalance') != 'null'
            ");
        }
    }

    public function down(): void
    {
        Schema::table('mpesa_transactions', function (Blueprint $table) {
            $table->dropColumn('org_account_balance_cents');
        });
    }
};
