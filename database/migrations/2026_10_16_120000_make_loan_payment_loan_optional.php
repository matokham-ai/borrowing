<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $foreignKey = DB::connection()->getDriverName() === 'sqlite'
            ? ['organisation_id', 'loan_id']
            : 'loan_payments_org_loan_fk';

        Schema::table('loan_payments', function (Blueprint $table) use ($foreignKey) {
            $table->dropForeign($foreignKey);
        });

        Schema::table('loan_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('loan_id')->nullable()->change();

            $table->foreign(
                ['organisation_id', 'loan_id'],
                'loan_payments_org_loan_fk'
            )->references(
                ['organisation_id', 'id']
            )->on('loans')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::table('loan_payments')->whereNull('loan_id')->exists()) {
            throw new RuntimeException(
                'Cannot make loan_payments.loan_id required while unmatched payments exist.'
            );
        }

        $foreignKey = DB::connection()->getDriverName() === 'sqlite'
            ? ['organisation_id', 'loan_id']
            : 'loan_payments_org_loan_fk';

        Schema::table('loan_payments', function (Blueprint $table) use ($foreignKey) {
            $table->dropForeign($foreignKey);
        });

        Schema::table('loan_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('loan_id')->nullable(false)->change();

            $table->foreign(
                ['organisation_id', 'loan_id'],
                'loan_payments_org_loan_fk'
            )->references(
                ['organisation_id', 'id']
            )->on('loans')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });
    }
};
