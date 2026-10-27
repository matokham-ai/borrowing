<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Borrower;
use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\LoanPayment;
use App\Models\LoanSchedule;
use App\Models\SystemEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $orgId    = Auth::user()->organisation_id;
        $now      = Carbon::now();

        $hour      = (int) $now->format('H');
        $greeting  = match(true) {
            $hour < 12 => 'Good morning',
            $hour < 17 => 'Good afternoon',
            default    => 'Good evening',
        };
        $firstName = explode(' ', Auth::user()->name ?? 'there')[0];
        $mStart   = $now->copy()->startOfMonth();
        $mEnd     = $now->copy()->endOfMonth();
        $pmStart  = $now->copy()->subMonth()->startOfMonth();
        $pmEnd    = $now->copy()->subMonth()->endOfMonth();

        // ── Stat: Total Borrowers ─────────────────────────────────────────────
        $totalBorrowers   = Borrower::where('organisation_id', $orgId)->count();
        $prevBorrowers    = Borrower::where('organisation_id', $orgId)
            ->where('registered_at', '<', $mStart)->count();
        $borrowersDelta   = $this->delta($totalBorrowers, $prevBorrowers);

        // ── Stat: Active Portfolio ────────────────────────────────────────────
        $activePortfolioCents = Loan::where('organisation_id', $orgId)
            ->whereIn('status', ['active', 'overdue'])
            ->sum('principal_cents');

        $prevPortfolioCents = Loan::where('organisation_id', $orgId)
            ->whereIn('status', ['active', 'overdue', 'completed'])
            ->where('disbursed_at', '<', $mStart)
            ->whereNull('completed_at')
            ->orWhere(fn($q) => $q
                ->where('organisation_id', $orgId)
                ->whereIn('status', ['active', 'overdue', 'completed'])
                ->where('disbursed_at', '<', $mStart)
                ->where('completed_at', '>=', $mStart)
            )
            ->sum('principal_cents');

        // Simpler: compare this month's disbursements vs last month's
        $thisMonthDisbursed = Loan::where('organisation_id', $orgId)
            ->whereBetween('disbursed_at', [$mStart, $mEnd])
            ->sum('principal_cents');
        $lastMonthDisbursed = Loan::where('organisation_id', $orgId)
            ->whereBetween('disbursed_at', [$pmStart, $pmEnd])
            ->sum('principal_cents');
        $portfolioDelta = $this->delta($thisMonthDisbursed, $lastMonthDisbursed);

        // ── Stat: Collection Rate ─────────────────────────────────────────────
        $totalPayableCents = Loan::where('organisation_id', $orgId)
            ->whereIn('status', ['active', 'overdue', 'completed'])
            ->sum('total_payable_cents');

        $collectedCents = LoanPayment::where('organisation_id', $orgId)
            ->where('status', 'posted')
            ->sum('amount_cents');

        $collectionRate = $totalPayableCents > 0
            ? round(($collectedCents / $totalPayableCents) * 100, 1)
            : 0;

        $prevCollected = LoanPayment::where('organisation_id', $orgId)
            ->where('status', 'posted')
            ->where('posted_at', '<', $mStart)
            ->sum('amount_cents');

        $prevPayable = Loan::where('organisation_id', $orgId)
            ->whereIn('status', ['active', 'overdue', 'completed'])
            ->where('disbursed_at', '<', $mStart)
            ->sum('total_payable_cents');

        $prevCollectionRate = $prevPayable > 0
            ? round(($prevCollected / $prevPayable) * 100, 1)
            : 0;

        $collectionDelta = $this->delta($collectionRate, $prevCollectionRate);

        // ── Stat: At Risk ─────────────────────────────────────────────────────
        $atRiskCents = Loan::where('organisation_id', $orgId)
            ->where('status', 'overdue')
            ->sum('principal_cents');

        $prevAtRiskCents = Loan::where('organisation_id', $orgId)
            ->where('status', 'overdue')
            ->where('disbursed_at', '<', $mStart)
            ->sum('principal_cents');

        $atRiskDelta = $this->delta($atRiskCents, $prevAtRiskCents);

        // ── Portfolio Health ──────────────────────────────────────────────────
        $statusCounts = Loan::where('organisation_id', $orgId)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $totalLoans = max($statusCounts->sum(), 1);
        $health = [
            'current'    => round(($statusCounts->get('active', 0) / $totalLoans) * 100),
            'watch'      => round(($statusCounts->get('overdue', 0) / $totalLoans) * 100),
            'delinquent' => round(($statusCounts->get('defaulted', 0) / $totalLoans) * 100),
            'default'    => round(($statusCounts->get('written_off', 0) / $totalLoans) * 100),
        ];

        // ── Chart: 6-month disbursements & collections ────────────────────────
        $chartMonthCount  = 6;
        $chartPeriod      = 'Last ' . $chartMonthCount . ' months';
        $chartMonths      = collect(range($chartMonthCount - 1, 0))->map(fn($i) => $now->copy()->subMonths($i)->startOfMonth());
        $chartLabels      = $chartMonths->map(fn($m) => $m->format('M'))->values();

        $disbursementRows = Loan::where('organisation_id', $orgId)
            ->whereNotNull('disbursed_at')
            ->where('disbursed_at', '>=', $chartMonths->first())
            ->selectRaw("DATE_FORMAT(disbursed_at, '%Y-%m') as month, SUM(principal_cents) as total")
            ->groupBy('month')
            ->pluck('total', 'month');

        $collectionRows = LoanPayment::where('organisation_id', $orgId)
            ->where('status', 'posted')
            ->whereNotNull('posted_at')
            ->where('posted_at', '>=', $chartMonths->first())
            ->selectRaw("DATE_FORMAT(posted_at, '%Y-%m') as month, SUM(amount_cents) as total")
            ->groupBy('month')
            ->pluck('total', 'month');

        $chartDisbursements = $chartMonths->map(fn($m) => round(($disbursementRows->get($m->format('Y-m'), 0)) / 100 / 1_000_000, 2))->values();
        $chartCollections   = $chartMonths->map(fn($m) => round(($collectionRows->get($m->format('Y-m'), 0)) / 100 / 1_000_000, 2))->values();

        $chartData = [
            'labels'        => $chartLabels,
            'disbursements' => $chartDisbursements,
            'collections'   => $chartCollections,
        ];

        // ── Upcoming Loan Dues ───────────────────────────────────────────────
        $today = $now->copy()->startOfDay();
        $upcomingLoanDues = LoanSchedule::with(['loan.borrower.user', 'loan.loanProduct'])
            ->where('organisation_id', $orgId)
            ->whereColumn('total_paid_cents', '<', 'total_due_cents')
            ->whereHas('loan', fn ($query) => $query
                ->whereIn('status', ['active', 'overdue']))
            ->orderBy('due_date')
            ->orderBy('installment_number')
            ->limit(5)
            ->get()
            ->map(function (LoanSchedule $schedule) use ($today) {
                $outstandingCents = max($schedule->total_due_cents - $schedule->total_paid_cents, 0);
                $daysRemaining = $today->diffInDays($schedule->due_date->copy()->startOfDay(), false);

                return [
                    'loan'             => 'LN-' . str_pad($schedule->loan->id, 5, '0', STR_PAD_LEFT),
                    'borrower'         => $schedule->loan->borrower->user->name ?? 'Unknown borrower',
                    'product'          => $schedule->loan->loanProduct->name ?? 'Loan facility',
                    'amount'           => 'KES ' . number_format($outstandingCents / 100, 2),
                    'due_date'         => $schedule->due_date->format('d M Y'),
                    'days_remaining'   => $daysRemaining,
                    'days_label'       => $daysRemaining < 0
                        ? abs($daysRemaining) . ' ' . (abs($daysRemaining) === 1 ? 'day' : 'days') . ' overdue'
                        : ($daysRemaining === 0
                            ? 'Due today'
                            : $daysRemaining . ' ' . ($daysRemaining === 1 ? 'day' : 'days') . ' remaining'),
                    'status_class'     => $daysRemaining < 0 ? 'danger' : ($daysRemaining === 0 ? 'warning' : 'success'),
                ];
            });

        // ── Recent Applications ───────────────────────────────────────────────
        $recentApplications = LoanApplication::with(['borrower.user', 'loanProduct'])
            ->where('loan_applications.organisation_id', $orgId)
            ->whereIn('status', ['pending', 'under_review', 'approved'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($app) {
                $score = (float) $app->behaviour_score;
                return [
                    'name'         => $app->borrower->user->name ?? 'Unknown',
                    'initials'     => collect(explode(' ', $app->borrower->user->name ?? 'U'))->map(fn($w) => strtoupper($w[0]))->take(2)->implode(''),
                    'ref'          => 'BRW-' . str_pad($app->borrower_id, 5, '0', STR_PAD_LEFT),
                    'product'      => $app->loanProduct->name ?? '—',
                    'amount'       => 'KES ' . number_format($app->requested_amount_cents / 100),
                    'risk'         => $score >= 70 ? 'Low' : ($score >= 40 ? 'Medium' : 'High'),
                    'risk_class'   => $score >= 70 ? 'success' : ($score >= 40 ? 'warning' : 'danger'),
                    'status'       => ucfirst(str_replace('_', ' ', $app->status)),
                    'status_class' => match($app->status) {
                        'approved'     => 'info',
                        'under_review' => 'warning',
                        default        => 'secondary',
                    },
                ];
            });

        // ── Recent Activity ───────────────────────────────────────────────────
        $recentActivity = SystemEvent::where('organisation_id', $orgId)
            ->orderByDesc('occurred_at')
            ->limit(5)
            ->get()
            ->map(function ($event) {
                $p = $event->payload ?? [];
                [$icon, $title, $detail] = match($event->event_type) {
                    'loan.approved'          => ['bi-check2-circle',    'Loan approved',      ($p['borrower'] ?? '') . ' · KES ' . number_format(($p['amount_cents'] ?? 0) / 100)],
                    'payment.received'       => ['bi-phone',            'Payment received',   ($p['method'] ?? 'M-Pesa') . ' · KES ' . number_format(($p['amount_cents'] ?? 0) / 100)],
                    'borrower.registered'    => ['bi-person-plus',      'New borrower',       ($p['borrower'] ?? '') . ' registered'],
                    'loan.delinquency_alert' => ['bi-exclamation-circle','Delinquency alert', 'Loan ' . ($p['loan_ref'] ?? '') . ' · ' . ($p['days_overdue'] ?? 0) . ' days'],
                    'borrower.tier_upgraded' => ['bi-shield-check',     'Tier upgraded',      ($p['borrower'] ?? '') . ' → ' . ($p['to_tier'] ?? '')],
                    default                  => ['bi-bell',             $event->event_type,   ''],
                };
                return ['icon' => $icon, 'title' => $title, 'detail' => $detail, 'diff' => $event->occurred_at->diffForHumans(null, true, true)];
            });

        return view('dashboard.index', [
            'greeting'         => $greeting,
            'firstName'        => $firstName,
            'totalBorrowers'   => number_format($totalBorrowers),
            'borrowersDelta'   => $borrowersDelta,
            'activePortfolio'  => 'KES ' . number_format($activePortfolioCents / 100 / 1_000_000, 1) . 'M',
            'portfolioDelta'   => $portfolioDelta,
            'collectionRate'   => $collectionRate . '%',
            'collectionDelta'  => $collectionDelta,
            'atRisk'           => 'KES ' . number_format($atRiskCents / 100 / 1_000_000, 1) . 'M',
            'atRiskDelta'      => $atRiskDelta,
            'health'           => $health,
            'chartData'        => $chartData,
            'chartPeriod'      => $chartPeriod,
            'upcomingLoanDues' => $upcomingLoanDues,
            'recentApplications' => $recentApplications,
            'recentActivity'   => $recentActivity,
        ]);
    }

    /**
     * Export a CSV snapshot of the portfolio for the authenticated organisation.
     *
     * Columns: loan_ref, borrower_name, phone, product, status, principal_kes,
     *          total_payable_kes, disbursed_at, matures_at, completed_at
     */
    public function export()
    {
        $orgId = (int) Auth::user()->organisation_id;

        $loans = DB::table('loans')
            ->join('borrowers', 'borrowers.id', '=', 'loans.borrower_id')
            ->join('users', 'users.id', '=', 'borrowers.user_id')
            ->join('loan_products', 'loan_products.id', '=', 'loans.loan_product_id')
            ->where('loans.organisation_id', $orgId)
            ->orderByDesc('loans.id')
            ->select([
                'loans.id',
                'users.name as borrower_name',
                'borrowers.phone_number',
                'loan_products.name as product_name',
                'loans.status',
                'loans.principal_cents',
                'loans.total_payable_cents',
                'loans.disbursed_at',
                'loans.matures_at',
                'loans.completed_at',
            ])
            ->get();

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="portfolio-' . now()->format('Y-m-d') . '.csv"',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($loans) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Excel compatibility
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Loan Ref', 'Borrower Name', 'Phone', 'Product', 'Status',
                'Principal (KES)', 'Total Payable (KES)',
                'Disbursed At', 'Matures At', 'Completed At',
            ]);

            foreach ($loans as $loan) {
                fputcsv($handle, [
                    'LN-' . str_pad($loan->id, 5, '0', STR_PAD_LEFT),
                    $loan->borrower_name,
                    $loan->phone_number,
                    $loan->product_name,
                    ucfirst($loan->status),
                    number_format($loan->principal_cents / 100, 2),
                    number_format($loan->total_payable_cents / 100, 2),
                    $loan->disbursed_at  ? Carbon::parse($loan->disbursed_at)->format('Y-m-d')  : '',
                    $loan->matures_at    ? Carbon::parse($loan->matures_at)->format('Y-m-d')    : '',
                    $loan->completed_at  ? Carbon::parse($loan->completed_at)->format('Y-m-d')  : '',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Returns ['value' => '±X.X%', 'direction' => 'up'|'down'|'neutral']
     */
    private function delta(float $current, float $previous): array
    {
        if ($previous == 0) {
            return ['value' => '—', 'direction' => 'neutral'];
        }
        $pct = round((($current - $previous) / $previous) * 100, 1);
        return [
            'value'     => ($pct >= 0 ? '+' : '') . $pct . '%',
            'direction' => $pct > 0 ? 'up' : ($pct < 0 ? 'down' : 'neutral'),
        ];
    }
}
