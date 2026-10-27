<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PERMISSIONS = [
        'view-dashboard' => 'View Dashboard',
        'view-borrowers' => 'View Borrowers',
        'create-borrowers' => 'Create Borrowers',
        'view-loans' => 'View Loans',
        'create-loans' => 'Create Loans',
        'loans.submit' => 'Submit loan applications',
        'loans.approve.first_level' => 'Approve loan applications at first level',
        'loans.approve.final' => 'Give final loan approval',
        'loans.return' => 'Return loan applications for correction',
        'loans.reject' => 'Reject loan applications',
        'loans.disburse' => 'Disburse approved loans',
        'collateral.verify' => 'Independently verify collateral',
        'branches.manage' => 'Manage organizational branches',
        'borrowers.verify_kyc' => 'Verify borrower identity documents',
    ];

    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organisation_id')->constrained('organisations')->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('code', 40);
            $table->string('county', 120)->nullable();
            $table->string('town', 120)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['organisation_id', 'code'], 'uniq_branches_org_code');
            $table->unique(['organisation_id', 'id'], 'uniq_branches_org_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('organisation_id')
                ->constrained('branches')->nullOnDelete();
            $table->index(['organisation_id', 'branch_id'], 'idx_users_org_branch');
        });

        Schema::table('borrowers', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('organisation_id')
                ->constrained('branches')->nullOnDelete();
            $table->foreignId('registered_by')->nullable()->after('branch_id')
                ->constrained('users')->nullOnDelete();
            $table->index(['organisation_id', 'branch_id'], 'idx_borrowers_org_branch');
            $table->index(['organisation_id', 'registered_by'], 'idx_borrowers_org_registrant');
        });

        Schema::table('loan_applications', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('organisation_id')
                ->constrained('branches')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->after('branch_id')
                ->constrained('users')->nullOnDelete();
            $table->foreignId('first_level_approved_by')->nullable()->after('decided_at')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('first_level_approved_at')->nullable()->after('first_level_approved_by');
            $table->text('first_level_reason')->nullable()->after('first_level_approved_at');
            $table->foreignId('final_approved_by')->nullable()->after('first_level_reason')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('final_approved_at')->nullable()->after('final_approved_by');
            $table->foreignId('returned_by')->nullable()->after('final_approved_at')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('returned_at')->nullable()->after('returned_by');
            $table->text('return_reason')->nullable()->after('returned_at');
            $table->index(['organisation_id', 'branch_id', 'status'], 'idx_applications_org_branch_status');
            $table->index(['organisation_id', 'created_by'], 'idx_applications_org_creator');
        });

        $now = now();
        foreach (self::PERMISSIONS as $name => $displayName) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $name],
                [
                    'display_name' => $displayName,
                    'description' => null,
                    'is_system' => true,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('name', array_keys(self::PERMISSIONS))
            ->pluck('id', 'name');

        foreach (DB::table('organisations')->pluck('id') as $organisationId) {
            $branchId = DB::table('branches')->insertGetId([
                'organisation_id' => $organisationId,
                'name' => 'Main Branch',
                'code' => 'MAIN',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('users')
                ->where('organisation_id', $organisationId)
                ->whereNull('branch_id')
                ->whereIn('id', DB::table('role_user')
                    ->join('roles', 'roles.id', '=', 'role_user.role_id')
                    ->where('role_user.organisation_id', $organisationId)
                    ->whereIn('roles.name', ['loan-officer', 'supervisor'])
                    ->select('role_user.user_id'))
                ->update(['branch_id' => $branchId]);

            DB::table('borrowers')
                ->where('organisation_id', $organisationId)
                ->whereNull('branch_id')
                ->update(['branch_id' => $branchId]);

            DB::table('loan_applications')
                ->where('organisation_id', $organisationId)
                ->whereNull('branch_id')
                ->update(['branch_id' => $branchId]);

            DB::table('loan_applications')
                ->where('organisation_id', $organisationId)
                ->update(['security_requirements_required' => true]);

            DB::table('loan_applications')
                ->where('organisation_id', $organisationId)
                ->where('status', 'approved')
                ->whereNotExists(function ($query) {
                    $query->selectRaw('1')
                        ->from('loans')
                        ->whereColumn('loans.loan_application_id', 'loan_applications.id')
                        ->whereColumn('loans.organisation_id', 'loan_applications.organisation_id');
                })
                ->update([
                    'status' => 'submitted',
                    'decision' => null,
                    'decision_reason' => null,
                    'decided_at' => null,
                    'approved_amount_cents' => null,
                    'approved_term_days' => null,
                    'security_requirements_required' => true,
                ]);

            $supervisorId = DB::table('roles')->where('organisation_id', $organisationId)
                ->where('name', 'supervisor')->value('id');

            if (!$supervisorId) {
                $supervisorId = DB::table('roles')->insertGetId([
                    'organisation_id' => $organisationId,
                    'name' => 'supervisor',
                    'display_name' => 'Supervisor',
                    'description' => 'Verifies loan applications and provides first-level approval.',
                    'is_system' => true,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $rolePermissions = [
                'supervisor' => [
                    'view-dashboard', 'view-borrowers', 'create-borrowers', 'view-loans', 'create-loans',
                    'borrowers.verify_kyc', 'loans.submit',
                    'loans.approve.first_level', 'loans.return', 'loans.reject', 'collateral.verify',
                ],
                'admin' => [
                    'create-borrowers', 'borrowers.verify_kyc', 'loans.submit',
                    'loans.approve.first_level', 'loans.approve.final', 'loans.return',
                    'loans.reject', 'loans.disburse', 'collateral.verify', 'branches.manage',
                ],
                'super-admin' => array_keys(self::PERMISSIONS),
                'loan-officer' => ['loans.submit'],
            ];

            foreach ($rolePermissions as $roleName => $names) {
                $roleId = $roleName === 'supervisor'
                    ? $supervisorId
                    : DB::table('roles')->where('organisation_id', $organisationId)
                        ->where('name', $roleName)->value('id');

                if (!$roleId) {
                    continue;
                }

                foreach ($names as $permissionName) {
                    if (!isset($permissionIds[$permissionName])) {
                        continue;
                    }

                    DB::table('permission_role')->insertOrIgnore([
                        'role_id' => $roleId,
                        'permission_id' => $permissionIds[$permissionName],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('name', array_keys(self::PERMISSIONS))->delete();

        Schema::table('loan_applications', function (Blueprint $table) {
            $table->dropIndex('idx_applications_org_branch_status');
            $table->dropIndex('idx_applications_org_creator');
            $table->dropConstrainedForeignId('branch_id');
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('first_level_approved_by');
            $table->dropColumn([
                'first_level_approved_at', 'first_level_reason', 'final_approved_at',
                'returned_at', 'return_reason',
            ]);
            $table->dropConstrainedForeignId('final_approved_by');
            $table->dropConstrainedForeignId('returned_by');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_org_branch');
            $table->dropConstrainedForeignId('branch_id');
        });

        Schema::table('borrowers', function (Blueprint $table) {
            $table->dropIndex('idx_borrowers_org_branch');
            $table->dropIndex('idx_borrowers_org_registrant');
            $table->dropConstrainedForeignId('branch_id');
            $table->dropConstrainedForeignId('registered_by');
        });

        Schema::dropIfExists('branches');
    }
};
