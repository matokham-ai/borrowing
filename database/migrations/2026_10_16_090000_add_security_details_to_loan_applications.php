<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loan_applications', function (Blueprint $table) {
            $table->longText('security_data')->nullable();
            $table->char('guarantor_id_fingerprint', 64)->nullable();
            $table->timestamp('guarantor_consent_at')->nullable();
            $table->boolean('security_requirements_required')->default(false);
            $table->timestamp('security_retention_until')->nullable();

            $table->index(
                ['organisation_id', 'guarantor_id_fingerprint'],
                'idx_applications_org_guarantor_fingerprint'
            );
            $table->index('security_retention_until', 'idx_applications_security_retention_until');
        });
    }

    public function down(): void
    {
        Schema::table('loan_applications', function (Blueprint $table) {
            $table->dropIndex('idx_applications_org_guarantor_fingerprint');
            $table->dropIndex('idx_applications_security_retention_until');
            $table->dropColumn([
                'security_data',
                'guarantor_id_fingerprint',
                'guarantor_consent_at',
                'security_requirements_required',
                'security_retention_until',
            ]);
        });
    }
};
