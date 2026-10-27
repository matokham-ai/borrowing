<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Support\Auditor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BranchController extends Controller
{
    public function index()
    {
        $branches = Branch::withCount(['users', 'loanApplications'])->orderBy('name')->get();

        return view('branches.index', compact('branches'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $branch = Branch::create([
            ...$data,
            'organisation_id' => auth()->user()->organisation_id,
            'is_active' => true,
        ]);

        Auditor::log('branch.created', $branch);

        return back()->with('success', "Branch {$branch->name} created.");
    }

    public function update(Request $request, Branch $branch)
    {
        $this->authorizeBranch($branch);
        $data = $this->validatedData($request, $branch);
        $old = $branch->only(['name', 'code', 'county', 'town']);
        $branch->update($data);

        Auditor::log('branch.updated', $branch, ['old' => $old, 'new' => $branch->only(array_keys($old))]);

        return back()->with('success', "Branch {$branch->name} updated.");
    }

    public function toggleActive(Branch $branch)
    {
        $this->authorizeBranch($branch);
        $branch->update(['is_active' => !$branch->is_active]);

        Auditor::log($branch->is_active ? 'branch.activated' : 'branch.deactivated', $branch);

        return back()->with('success', "Branch {$branch->name} " . ($branch->is_active ? 'activated.' : 'deactivated.'));
    }

    private function validatedData(Request $request, ?Branch $branch = null): array
    {
        $organisationId = (int) auth()->user()->organisation_id;

        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => [
                'required', 'string', 'max:40', 'alpha_dash',
                Rule::unique('branches', 'code')
                    ->where('organisation_id', $organisationId)
                    ->ignore($branch?->id),
            ],
            'county' => ['nullable', 'string', 'max:120'],
            'town' => ['nullable', 'string', 'max:120'],
        ]);
    }

    private function authorizeBranch(Branch $branch): void
    {
        abort_unless((int) $branch->organisation_id === (int) auth()->user()->organisation_id, 404);
    }
}
