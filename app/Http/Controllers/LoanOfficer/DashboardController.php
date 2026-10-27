<?php

namespace App\Http\Controllers\LoanOfficer;

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

        $applications = LoanApplication::query()
            ->where('organisation_id', $user->organisation_id)
            ->where('created_by', $user->id);

        $borrowers = Borrower::query()
            ->where('organisation_id', $user->organisation_id)
            ->where('registered_by', $user->id);

        $loans = Loan::query()
            ->where('organisation_id', $user->organisation_id)
            ->whereHas('loanApplication', fn ($query) => $query
                ->where('organisation_id', $user->organisation_id)
                ->where('created_by', $user->id));

        $recentApplications = (clone $applications)
            ->with(['borrower.user', 'loanProduct', 'loan'])
            ->latest('created_at')
            ->limit(6)
            ->get();

        $stats = [
            'applications' => (clone $applications)->count(),
            'in_progress' => (clone $applications)
                ->whereNotIn('status', ['rejected', 'declined', 'withdrawn', 'expired'])
                ->where(function ($query) {
                    $query->where('status', '!=', 'loan_created')
                        ->orWhereDoesntHave('loan', fn ($loan) => $loan
                            ->whereIn('status', ['active', 'overdue', 'completed', 'defaulted', 'written_off']));
                })
                ->count(),
            'awaiting_supervisor' => (clone $applications)->whereIn('status', ['submitted', 'under_review'])->count(),
            'awaiting_admin' => (clone $applications)->where('status', 'first_level_approved')->count(),
            'awaiting_disbursement' => (clone $applications)
                ->where(function ($query) {
                    $query->where('status', 'approved')
                        ->orWhere(function ($query) {
                            $query->where('status', 'loan_created')
                                ->whereDoesntHave('loan', fn ($loan) => $loan
                                ->whereIn('status', ['active', 'overdue', 'completed', 'defaulted', 'written_off']));
                        });
                })
                ->count(),
            'approved' => (clone $applications)->where('status', 'approved')->count(),
            'borrowers' => (clone $borrowers)->count(),
            'active_loans' => (clone $loans)->whereIn('status', ['active', 'overdue'])->count(),
            'portfolio_cents' => (clone $loans)->whereIn('status', ['active', 'overdue'])->sum('principal_cents'),
        ];

        return view('loan-officer.dashboard', [
            'branch' => $user->branch,
            'firstName' => explode(' ', $user->name ?? 'there')[0],
            'recentApplications' => $recentApplications,
            'stats' => $stats,
        ]);
    }
}
