<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PERMISSIONS = [
        'view-dashboard',
        'view-borrowers',
        'create-borrowers',
        'edit-borrowers',
        'view-loans',
        'create-loans',
        'loans.submit',
    ];

    public function up(): void
    {
        $permissionIds = DB::table('permissions')
            ->whereIn('name', self::PERMISSIONS)
            ->pluck('id');

        if ($permissionIds->isEmpty()) {
            return;
        }

        $now = now();
        $roleIds = DB::table('roles')
            ->where('name', 'loan-officer')
            ->pluck('id');

        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('permission_role')->insertOrIgnore([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Preserve role grants because some may have existed before this migration.
    }
};
