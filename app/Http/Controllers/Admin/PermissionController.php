<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PermissionController extends Controller
{
    /**
     * Grouped permission listing.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $group  = $request->input('group');

        $query = Permission::withCount('roles')->orderBy('name');

        if ($search !== '') {
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('display_name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
            );
        }

        $all = $query->get();

        // Group by the second segment of the slug: "approve-loans" → "loans"
        $permissions = $all->groupBy(fn ($p) => explode('-', $p->name, 2)[1] ?? 'general');

        // Filter to a single group after grouping (keeps counts accurate)
        if ($group && $permissions->has($group)) {
            $permissions = $permissions->only([$group]);
        }

        $groups = $all
            ->groupBy(fn ($p) => explode('-', $p->name, 2)[1] ?? 'general')
            ->keys()
            ->sort()
            ->values();

        $totalCount  = $all->count();
        $activeCount = $all->where('is_active', true)->count();

        return view('permissions.index', compact(
            'permissions', 'groups',
            'search', 'group',
            'totalCount', 'activeCount'
        ));
    }

    /**
     * Store a new permission.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:100',
                               'regex:/^[a-z0-9\-]+$/',
                               Rule::unique('permissions', 'name')],
            'display_name' => ['required', 'string', 'max:120'],
            'description'  => ['nullable', 'string', 'max:500'],
            'is_active'    => ['boolean'],
        ]);

        Permission::create([
            'name'         => $data['name'],
            'display_name' => $data['display_name'],
            'description'  => $data['description'] ?? null,
            'is_system'    => false,
            'is_active'    => $request->boolean('is_active', true),
        ]);

        return back()->with('success', "Permission \"{$data['display_name']}\" created.");
    }

    /**
     * Update an existing permission.
     * System permissions: only display_name, description and is_active can change.
     * The slug (name) of a system permission is immutable.
     */
    public function update(Request $request, Permission $permission)
    {
        $nameRules = $permission->is_system
            ? []   // name field is not sent for system permissions
            : ['required', 'string', 'max:100', 'regex:/^[a-z0-9\-]+$/',
               Rule::unique('permissions', 'name')->ignore($permission->id)];

        $data = $request->validate([
            'name'         => $nameRules,
            'display_name' => ['required', 'string', 'max:120'],
            'description'  => ['nullable', 'string', 'max:500'],
            'is_active'    => ['boolean'],
        ]);

        $permission->update([
            ...(!$permission->is_system && isset($data['name'])
                ? ['name' => $data['name']] : []),
            'display_name' => $data['display_name'],
            'description'  => $data['description'] ?? null,
            'is_active'    => $request->boolean('is_active'),
        ]);

        return back()->with('success', "Permission \"{$permission->display_name}\" updated.");
    }

    /**
     * Toggle active/inactive status.
     */
    public function toggleActive(Permission $permission)
    {
        $permission->update(['is_active' => ! $permission->is_active]);

        $state = $permission->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Permission \"{$permission->display_name}\" {$state}.");
    }

    /**
     * Delete a permission. System permissions are protected.
     */
    public function destroy(Permission $permission)
    {
        abort_if($permission->is_system, 403, 'System permissions cannot be deleted.');

        $name = $permission->display_name;
        $permission->delete();

        return back()->with('success', "Permission \"{$name}\" deleted.");
    }
}
