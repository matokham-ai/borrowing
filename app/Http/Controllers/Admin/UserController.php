<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Role;
use App\Support\Auditor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('roles')
            ->where('organisation_id', auth()->user()->organisation_id)
            ->where('id', '!=', auth()->id());

        if ($search = $request->search) {
            $query->where(fn ($q) => $q->where('name', 'like', "%$search%")
                ->orWhere('email', 'like', "%$search%"));
        }

        if ($request->role) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $request->role));
        }

        if ($request->status !== null && $request->status !== '') {
            $query->where('is_active', (bool) $request->status);
        }

        $users = $query->latest()->paginate(15)->withQueryString();
        $roles  = Role::where('organisation_id', auth()->user()->organisation_id)->get();

        return view('users.index', compact('users', 'roles'));
    }

    public function create()
    {
        $roles = Role::where('organisation_id', auth()->user()->organisation_id)
            ->where('is_active', true)->get();
        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('users.create', compact('roles', 'branches'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'password' => ['required', Password::min(8)->mixedCase()->numbers()],
            'roles'    => ['nullable', 'array'],
            'roles.*'  => [Rule::exists('roles', 'id')->where('organisation_id', auth()->user()->organisation_id)],
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')
                ->where('organisation_id', auth()->user()->organisation_id)->where('is_active', true)],
            'is_active'=> ['boolean'],
        ]);
        $this->assertBranchForStaffRole($data['roles'] ?? [], $data['branch_id'] ?? null);

        $user = User::create([
            'organisation_id' => auth()->user()->organisation_id,
            'branch_id'       => $data['branch_id'] ?? null,
            'name'            => $data['name'],
            'email'           => $data['email'],
            'password'        => $data['password'],
            'is_active'       => $request->boolean('is_active', true),
        ]);

        if (!empty($data['roles'])) {
            $user->roles()->syncWithPivotValues(
                $data['roles'],
                ['organisation_id' => auth()->user()->organisation_id]
            );
        }

        Auditor::log('user.created', $user, ['new' => ['roles' => $data['roles'] ?? [], 'branch_id' => $user->branch_id]]);

        return redirect()->route('users.index')
            ->with('success', "User {$user->name} created successfully.");
    }

    public function edit(User $user)
    {
        $this->authoriseUser($user);

        $roles    = Role::where('organisation_id', auth()->user()->organisation_id)
            ->where('is_active', true)->get();
        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $userRoles = $user->roles->pluck('id')->toArray();

        return view('users.edit', compact('user', 'roles', 'userRoles', 'branches'));
    }

    public function update(Request $request, User $user)
    {
        $this->authoriseUser($user);

        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', "unique:users,email,{$user->id}"],
            'password' => ['nullable', Password::min(8)->mixedCase()->numbers()],
            'roles'    => ['nullable', 'array'],
            'roles.*'  => [Rule::exists('roles', 'id')->where('organisation_id', auth()->user()->organisation_id)],
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')
                ->where('organisation_id', auth()->user()->organisation_id)->where('is_active', true)],
            'is_active'=> ['boolean'],
        ]);
        $this->assertBranchForStaffRole($data['roles'] ?? [], $data['branch_id'] ?? null);

        $oldValues = ['branch_id' => $user->branch_id, 'roles' => $user->roles->pluck('id')->all()];
        $user->update([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'branch_id' => $data['branch_id'] ?? null,
            'is_active' => $request->boolean('is_active'),
            ...($data['password'] ? ['password' => $data['password']] : []),
        ]);

        $user->roles()->syncWithPivotValues(
            $data['roles'] ?? [],
            ['organisation_id' => auth()->user()->organisation_id]
        );
        Auditor::log('user.access_updated', $user, [
            'old' => $oldValues,
            'new' => ['branch_id' => $user->branch_id, 'roles' => $data['roles'] ?? []],
        ]);

        return redirect()->route('users.index')
            ->with('success', "User {$user->name} updated successfully.");
    }

    public function destroy(User $user)
    {
        $this->authoriseUser($user);
        $name = $user->name;
        $user->delete();

        return redirect()->route('users.index')
            ->with('success', "User {$name} deleted.");
    }

    public function toggleActive(User $user)
    {
        $this->authoriseUser($user);
        $user->update(['is_active' => !$user->is_active]);

        return back()->with('success', "User {$user->name} " . ($user->is_active ? 'activated' : 'deactivated') . '.');
    }

    private function authoriseUser(User $user): void
    {
        abort_if($user->organisation_id !== auth()->user()->organisation_id, 403);
    }

    private function assertBranchForStaffRole(array $roleIds, mixed $branchId): void
    {
        $requiresBranch = Role::where('organisation_id', auth()->user()->organisation_id)
            ->whereIn('id', $roleIds)
            ->whereIn('name', ['loan-officer', 'supervisor'])
            ->exists();

        if ($requiresBranch && !$branchId) {
            abort(422, 'Assign a branch to loan officers and supervisors.');
        }
    }
}
