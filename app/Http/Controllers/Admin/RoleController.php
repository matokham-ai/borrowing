<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')
            ->withCount('users')
            ->where('organisation_id', auth()->user()->organisation_id)
            ->latest()
            ->get();

        return view('roles.index', compact('roles'));
    }

    public function create()
    {
        $permissions = Permission::where('is_active', true)->orderBy('name')->get()
            ->groupBy(fn ($p) => explode('-', $p->name, 2)[1] ?? 'general');

        return view('roles.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:80', 'regex:/^[a-z0-9\-]+$/'],
            'display_name' => ['required', 'string', 'max:120'],
            'description'  => ['nullable', 'string', 'max:500'],
            'permissions'  => ['nullable', 'array'],
            'permissions.*'=> ['exists:permissions,id'],
        ]);

        $exists = Role::where('organisation_id', auth()->user()->organisation_id)
            ->where('name', $data['name'])->exists();

        abort_if($exists, 422, 'A role with that name already exists.');

        $role = Role::create([
            'organisation_id' => auth()->user()->organisation_id,
            'name'            => $data['name'],
            'display_name'    => $data['display_name'],
            'description'     => $data['description'] ?? null,
            'is_system'       => false,
            'is_active'       => true,
        ]);

        $role->permissions()->sync($data['permissions'] ?? []);

        return redirect()->route('roles.index')
            ->with('success', "Role \"{$role->display_name}\" created.");
    }

    public function edit(Role $role)
    {
        $this->authoriseRole($role);

        $permissions = Permission::where('is_active', true)->orderBy('name')->get()
            ->groupBy(fn ($p) => explode('-', $p->name, 2)[1] ?? 'general');

        $assigned = $role->permissions->pluck('id')->toArray();

        return view('roles.edit', compact('role', 'permissions', 'assigned'));
    }

    public function update(Request $request, Role $role)
    {
        $this->authoriseRole($role);

        $data = $request->validate([
            'display_name' => ['required', 'string', 'max:120'],
            'description'  => ['nullable', 'string', 'max:500'],
            'permissions'  => ['nullable', 'array'],
            'permissions.*'=> ['exists:permissions,id'],
        ]);

        $role->update([
            'display_name' => $data['display_name'],
            'description'  => $data['description'] ?? null,
        ]);

        $role->permissions()->sync($data['permissions'] ?? []);

        return redirect()->route('roles.index')
            ->with('success', "Role \"{$role->display_name}\" updated.");
    }

    public function destroy(Role $role)
    {
        $this->authoriseRole($role);
        abort_if($role->is_system, 403, 'System roles cannot be deleted.');

        $name = $role->display_name;
        $role->delete();

        return redirect()->route('roles.index')
            ->with('success', "Role \"{$name}\" deleted.");
    }

    private function authoriseRole(Role $role): void
    {
        abort_if($role->organisation_id !== auth()->user()->organisation_id, 403);
    }
}
