<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('borrowers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organisation_id')
                ->constrained('organisations')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('user_id');

            $table->string('phone_number', 15); // Canonical Kenya format: 2547XXXXXXXX

            $table->string('account_status', 30)
                ->default('active')
                ->index();

            // Cached current graduation level. Historical progression lives in tier_histories.
            $table->unsignedBigInteger('current_tier_id')->nullable();

            // Derived behavioural counters for fast appraisal; authoritative events/loans
            // remain the source of truth.
            $table->unsignedInteger('lifetime_loans_count')->default(0);
            $table->unsignedInteger('successful_loans_count')->default(0);
            $table->unsignedInteger('on_time_loans_count')->default(0);
            $table->unsignedInteger('consecutive_on_time_loans')->default(0);
            $table->unsignedInteger('late_loans_count')->default(0);
            $table->unsignedInteger('defaulted_loans_count')->default(0);

            $table->decimal('behaviour_score', 8, 4)->default(0);

            $table->timestamp('registered_at')->nullable();
            $table->timestamp('last_loan_at')->nullable();
            $table->timestamp('last_repayment_at')->nullable();
            $table->timestamp('last_graduated_at')->nullable();

            $table->timestamps();

            // A user may be a borrower once per organisation.
            $table->unique(['organisation_id', 'user_id'], 'uniq_borrowers_organisation_id_user_id');;

            // A phone number belongs to one borrower profile per organisation.
            $table->unique(['organisation_id', 'phone_number'], 'uniq_borrowers_organisation_id_phone_number');;

            // Required for tenant-safe composite foreign keys.
            $table->unique(['organisation_id', 'id'], 'uniq_borrowers_organisation_id_id');;

            $table->foreign(
                ['organisation_id', 'user_id'],
                'borrowers_org_user_fk'
            )->references(
                ['organisation_id', 'id']
            )->on('users')
             ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('borrowers');
    }
};
