<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\Borrower;
use App\Models\Loan;
use App\Models\LoanApplication;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $branchId = (int) ($user->branch_id ?? 0);

        $applications = LoanApplication::query()
            ->where('organisation_id', $user->organisation_id)
            ->where('branch_id', $branchId);

        $borrowers = Borrower::query()
            ->where('organisation_id', $user->organisation_id)
            ->where('branch_id', $branchId);

        $loans = Loan::query()
            ->where('organisation_id', $user->organisation_id)
            ->whereHas('loanApplication', fn ($query) => $query
                ->where('organisation_id', $user->organisation_id)
                ->where('branch_id', $branchId));

        $reviewQueue = (clone $applications)
            ->whereIn('status', ['submitted', 'under_review'])
            ->with(['borrower.user', 'loanProduct', 'creator'])
            ->latest('submitted_at')
            ->limit(6)
            ->get();

        $stats = [
            'review_queue' => (clone $applications)->whereIn('status', ['submitted', 'under_review'])->count(),
            'first_level_approved' => (clone $applications)->where('status', 'first_level_approved')->count(),
            'returned' => (clone $applications)->where('status', 'returned')->count(),
            'borrowers' => (clone $borrowers)->count(),
            'active_loans' => (clone $loans)->whereIn('status', ['active', 'overdue'])->count(),
            'portfolio_cents' => (clone $loans)->whereIn('status', ['active', 'overdue'])->sum('principal_cents'),
        ];

        return view('supervisor.dashboard', [
            'branch' => $user->branch,
            'firstName' => explode(' ', $user->name ?? 'there')[0],
            'reviewQueue' => $reviewQueue,
            'stats' => $stats,
        ]);
    }
}
