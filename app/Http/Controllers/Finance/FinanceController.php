<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\LoanPayment;
use App\Models\LoanProduct;
use App\Models\MpesaTransaction;
use App\Models\Loan;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FinanceController extends Controller
{
    // ──────────────────────────────────────────────────────────────────────────
    //  Finance Overview — the cockpit landing page
    // ──────────────────────────────────────────────────────────────────────────

    public function overview(Request $request)
    {
        $orgId  = Auth::user()->organisation_id;
        $period = $request->string('period', 'month')->toString(); // today | week | month

        [$start, $prevStart, $prevEnd] = $this->periodBounds($period);

        $base     = fn () => LoanPayment::where('organisation_id', $orgId);
        $inPeriod = fn () => ($base)()->where('received_at', '>=', $start);

        // ── KPI numbers ──────────────────────────────────────────────────────
        $totalCollected  = ($inPeriod)()->sum('amount_cents');
        $reconciled      = ($inPeriod)()->where('status', 'posted')->sum('amount_cents');
        $pending         = ($inPeriod)()->whereIn('status', ['pending', 'processing'])->sum('amount_cents');
        $pendingCount    = ($inPeriod)()->whereIn('status', ['pending', 'processing'])->count();

        // Previous period for delta
        $prevBase     = ($base)()->where('received_at', '>=', $prevStart)->where('received_at', '<', $prevEnd ?? now());
        $prevTotal    = $prevBase->sum('amount_cents');
        $totalDeltaPct = $prevTotal > 0 ? round((($totalCollected - $prevTotal) / $prevTotal) * 100, 1) : null;

        // Reconciliation rate
        $recoRate    = $totalCollected > 0 ? round(($reconciled / $totalCollected) * 100, 1) : 0;
        $matchedCount   = ($inPeriod)()->where('status', 'posted')->count();
        $totalCount     = ($inPeriod)()->count();
        $exceptionCount = ($inPeriod)()->whereIn('status', ['failed'])->count()
                        + ($inPeriod)()->whereNull('loan_id')->where('status', '!=', 'reversed')->count();
        $exceptionCount = min($exceptionCount, max(0, $totalCount - $matchedCount - $pendingCount));

        // Exceptions (unmatched + failed)
        $exceptionsCount = ($inPeriod)()->where(function ($q) {
            $q->whereNull('loan_id')->where('status', '!=', 'reversed')
              ->orWhere('status', 'failed');
        })->count();

        // ── M-Pesa account balance (most recent C2B with a balance figure) ───
        $mpesaBalanceTx = MpesaTransaction::where('organisation_id', $orgId)
            ->whereIn('transaction_type', ['c2b_confirmation', 'c2b'])
            ->whereNotNull('org_account_balance_cents')
            ->where('org_account_balance_cents', '>', 0)
            ->latest('transacted_at')
            ->first();
        $mpesaBalance      = $mpesaBalanceTx?->org_account_balance_cents;
        $mpesaBalanceAt    = $mpesaBalanceTx?->transacted_at ?? $mpesaBalanceTx?->received_at;

        // ── Collections trend — 14 daily buckets ────────────────────────────
        $trendBuckets = $this->buildDailyBuckets($orgId, 14);

        // ── Collections by channel ───────────────────────────────────────────
        $channelTotals = ($inPeriod)()
            ->selectRaw('payment_method, SUM(amount_cents) as total_cents')
            ->groupBy('payment_method')
            ->pluck('total_cents', 'payment_method');

        $channels = [
            'mpesa'          => ['label' => 'M-Pesa',         'color' => '#12b76a'],
            'bank_transfer'  => ['label' => 'Bank Transfer',  'color' => '#7f56d9'],
            'cash'           => ['label' => 'Cash',           'color' => '#f79009'],
            'credit_balance' => ['label' => 'Other',          'color' => '#98a2b3'],
        ];
        $channelData = collect($channels)->map(function ($meta, $key) use ($channelTotals, $totalCollected) {
            $cents = (int) ($channelTotals[$key] ?? 0);
            return [
                'label' => $meta['label'],
                'color' => $meta['color'],
                'cents' => $cents,
                'pct'   => $totalCollected > 0 ? round(($cents / $totalCollected) * 100) : 0,
            ];
        })->filter(fn ($r) => $r['cents'] > 0)->values();

        // ── Recent financial activity ────────────────────────────────────────
        $recentActivity = ($base)()
            ->with(['borrower.user', 'loan.loanProduct', 'mpesaTransaction'])
            ->whereNotNull('received_at')
            ->latest('received_at')
            ->take(8)
            ->get();

        // ── Open exceptions (unmatched / failed) ─────────────────────────────
        $openExceptions = ($base)()
            ->with(['borrower.user', 'loan'])
            ->where(function ($q) {
                $q->whereNull('loan_id')->where('status', '!=', 'reversed')
                  ->orWhere('status', 'failed');
            })
            ->latest('received_at')
            ->take(5)
            ->get();

        return view('finance.overview', compact(
            'period',
            'totalCollected', 'reconciled', 'pending', 'pendingCount',
            'totalDeltaPct', 'recoRate', 'matchedCount', 'totalCount',
            'exceptionCount', 'exceptionsCount',
            'mpesaBalance', 'mpesaBalanceAt',
            'trendBuckets', 'channelData',
            'recentActivity', 'openExceptions',
        ));
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Reports
    // ──────────────────────────────────────────────────────────────────────────

    public function reports(Request $request)
    {
        $orgId     = Auth::user()->organisation_id;
        $period    = $request->string('period', 'month')->toString();
        $tab       = $request->string('tab', 'overview')->toString();

        [$start, $prevStart, $prevEnd] = $this->periodBounds($period);

        $base     = fn () => LoanPayment::where('organisation_id', $orgId);
        $inPeriod = fn () => ($base)()->where('received_at', '>=', $start);

        // ── KPI header cards ─────────────────────────────────────────────────
        $collections  = ($inPeriod)()->sum('amount_cents');
        $repayments   = ($inPeriod)()->where('status', 'posted')->sum('amount_cents');
        $reversals    = ($base)()->where('status', 'reversed')
                           ->where('reversed_at', '>=', $start)->sum('amount_cents');
        $reversalsCnt = ($base)()->where('status', 'reversed')
                           ->where('reversed_at', '>=', $start)->count();
        $matched      = ($inPeriod)()->where('status', 'posted')->count();
        $total        = ($inPeriod)()->count();
        $recoRate     = $total > 0 ? round(($matched / $total) * 100, 1) : 0;
        $pending      = $total - $matched;

        // Previous period deltas
        $prevColl   = ($base)()->where('received_at', '>=', $prevStart)->where('received_at', '<', $prevEnd ?? now())->sum('amount_cents');
        $prevRep    = ($base)()->where('received_at', '>=', $prevStart)->where('received_at', '<', $prevEnd ?? now())->where('status', 'posted')->sum('amount_cents');
        $prevRev    = ($base)()->where('status', 'reversed')->where('reversed_at', '>=', $prevStart)->where('reversed_at', '<', $prevEnd ?? now())->sum('amount_cents');

        $collDelta = $prevColl > 0 ? round((($collections - $prevColl) / $prevColl) * 100, 1) : null;
        $repDelta  = $prevRep  > 0 ? round((($repayments - $prevRep)   / $prevRep)  * 100, 1) : null;
        $revDelta  = $prevRev  > 0 ? round((($reversals  - $prevRev)   / $prevRev)  * 100, 1) : null;

        // ── Collections by channel ───────────────────────────────────────────
        $channelTotals = ($inPeriod)()
            ->selectRaw('payment_method, SUM(amount_cents) as total_cents, COUNT(*) as cnt')
            ->groupBy('payment_method')
            ->get()
            ->keyBy('payment_method');

        $channelRows = collect([
            'mpesa'         => ['label' => 'M-Pesa',        'color' => '#12b76a'],
            'bank_transfer' => ['label' => 'Bank Transfer', 'color' => '#7f56d9'],
            'cash'          => ['label' => 'Cash',          'color' => '#f79009'],
        ])->map(function ($meta, $key) use ($channelTotals, $collections) {
            $row = $channelTotals[$key] ?? null;
            $cents = (int) ($row?->total_cents ?? 0);
            return [
                'label' => $meta['label'],
                'color' => $meta['color'],
                'cents' => $cents,
                'count' => (int) ($row?->cnt ?? 0),
                'pct'   => $collections > 0 ? round(($cents / $collections) * 100) : 0,
            ];
        })->filter(fn ($r) => $r['cents'] > 0 || $r['count'] > 0)->values();

        // ── Monthly trend (12 months rolling) ───────────────────────────────
        $monthlyTrend = $this->buildMonthlyBuckets($orgId, 12);

        // ── Daily trend (30 days) ────────────────────────────────────────────
        $dailyTrend = $this->buildDailyBuckets($orgId, 30);

        return view('finance.reports', compact(
            'period', 'tab',
            'collections', 'repayments', 'reversals', 'reversalsCnt',
            'collDelta', 'repDelta', 'revDelta',
            'recoRate', 'matched', 'total', 'pending',
            'channelRows', 'monthlyTrend', 'dailyTrend',
        ));
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Reconciliation workspace
    // ──────────────────────────────────────────────────────────────────────────

    public function reconciliation(Request $request)
    {
        $orgId  = Auth::user()->organisation_id;
        $period = $request->string('period', 'today')->toString();
        $tab    = $request->string('tab', 'all')->toString();
        $search = trim($request->string('search')->toString());
        $method = $request->string('method')->toString();

        [$start] = $this->periodBounds($period);

        $base = fn () => LoanPayment::where('organisation_id', $orgId)
            ->where('received_at', '>=', $start);

        // ── Summary tiles ────────────────────────────────────────────────────
        $matched   = ($base)()->where('status', 'posted')->count();
        $pending   = ($base)()->whereIn('status', ['pending', 'processing'])->count();
        $failed    = ($base)()->where('status', 'failed')->count();
        $unmatched = ($base)()->whereNull('loan_id')->where('status', '!=', 'reversed')->count();
        $exceptions = $failed + $unmatched;
        $totalTx   = ($base)()->count();
        $recoRate  = $totalTx > 0 ? round(($matched / $totalTx) * 100, 1) : 0;

        // Amount difference (unapplied balances = "mismatch" proxy)
        $differenceCents = ($base)()->where('unapplied_cents', '>', 0)->sum('unapplied_cents');

        // ── Paginated transaction list ───────────────────────────────────────
        $query = ($base)()
            ->with(['borrower.user', 'loan.loanProduct', 'mpesaTransaction']);

        $query->when($tab === 'matched',    fn ($q) => $q->where('status', 'posted'))
              ->when($tab === 'pending',    fn ($q) => $q->whereIn('status', ['pending', 'processing']))
              ->when($tab === 'exceptions', fn ($q) => $q->where(function ($inner) {
                  $inner->whereNull('loan_id')->where('status', '!=', 'reversed')
                        ->orWhere('status', 'failed');
              }))
              ->when($method, fn ($q) => $q->where('payment_method', $method))
              ->when($search, fn ($q) => $q->where(function ($n) use ($search) {
                  $n->where('mpesa_receipt_number', 'like', "%{$search}%")
                    ->orWhere('external_reference',  'like', "%{$search}%")
                    ->orWhereHas('borrower.user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
              }));

        $transactions = $query->latest('received_at')->paginate(15)->withQueryString();

        // ── Open exceptions for the attention panel ──────────────────────────
        $openExceptions = LoanPayment::where('organisation_id', $orgId)
            ->with(['borrower.user', 'loan'])
            ->where(function ($q) {
                $q->whereNull('loan_id')->where('status', '!=', 'reversed')
                  ->orWhere('status', 'failed');
            })
            ->latest('received_at')
            ->take(10)
            ->get();

        // ── Latest M-Pesa account balance ────────────────────────────────────
        $mpesaBalanceTx = MpesaTransaction::where('organisation_id', $orgId)
            ->whereIn('transaction_type', ['c2b_confirmation', 'c2b'])
            ->whereNotNull('org_account_balance_cents')
            ->where('org_account_balance_cents', '>', 0)
            ->latest('transacted_at')
            ->first();
        $mpesaBalance   = $mpesaBalanceTx?->org_account_balance_cents;
        $mpesaBalanceAt = $mpesaBalanceTx?->transacted_at ?? $mpesaBalanceTx?->received_at;

        return view('finance.reconciliation', compact(
            'period', 'tab', 'search', 'method',
            'matched', 'pending', 'exceptions', 'differenceCents',
            'recoRate', 'totalTx',
            'transactions', 'openExceptions',
            'mpesaBalance', 'mpesaBalanceAt',
        ));
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Transactions ledger
    // ──────────────────────────────────────────────────────────────────────────

    public function transactions(Request $request)
    {
        $orgId  = Auth::user()->organisation_id;
        $period = $request->string('period', '30')->toString();
        $status = $request->string('status')->toString();
        $method = $request->string('method')->toString();
        $search = trim($request->string('search')->toString());

        $start = match ($period) {
            'today' => Carbon::today(),
            '7'     => Carbon::now()->subDays(7)->startOfDay(),
            '90'    => Carbon::now()->subDays(90)->startOfDay(),
            default => Carbon::now()->subDays(30)->startOfDay(),
        };

        $base = fn () => LoanPayment::where('organisation_id', $orgId)
            ->where('received_at', '>=', $start);

        $stats = [
            'total'     => ($base)()->sum('amount_cents'),
            'posted'    => ($base)()->where('status', 'posted')->sum('amount_cents'),
            'pending'   => ($base)()->whereIn('status', ['pending', 'processing'])->count(),
            'reversed'  => ($base)()->where('status', 'reversed')->sum('amount_cents'),
        ];

        $transactions = ($base)()
            ->with(['borrower.user', 'loan.loanProduct', 'mpesaTransaction'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($method, fn ($q) => $q->where('payment_method', $method))
            ->when($search, fn ($q) => $q->where(function ($n) use ($search) {
                $n->where('mpesa_receipt_number', 'like', "%{$search}%")
                  ->orWhere('external_reference',  'like', "%{$search}%")
                  ->orWhereHas('borrower.user', fn ($u) => $u->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('loan', fn ($l) => $l->where('id', $search));
            }))
            ->latest('received_at')
            ->paginate(20)
            ->withQueryString();

        return view('finance.transactions', compact(
            'period', 'status', 'method', 'search',
            'stats', 'transactions',
        ));
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Reversals  (re-uses PaymentController data shape but finance-framed)
    // ──────────────────────────────────────────────────────────────────────────

    public function reversals(Request $request)
    {
        $orgId  = Auth::user()->organisation_id;
        $period = $request->string('period', '30')->toString();
        $search = trim($request->string('search')->toString());
        $method = $request->string('method')->toString();

        $start = match ($period) {
            'today' => Carbon::today(),
            '7'     => Carbon::now()->subDays(7)->startOfDay(),
            '90'    => Carbon::now()->subDays(90)->startOfDay(),
            default => Carbon::now()->subDays(30)->startOfDay(),
        };

        $base = fn () => LoanPayment::where('organisation_id', $orgId)
            ->where('status', 'reversed');

        $stats = [
            'total_cents'  => ($base)()->where('reversed_at', '>=', $start)->sum('amount_cents'),
            'count'        => ($base)()->where('reversed_at', '>=', $start)->count(),
            'avg_cents'    => ($base)()->where('reversed_at', '>=', $start)->avg('amount_cents') ?? 0,
            'sparkline'    => $this->buildSparkline(($base)(), 'reversed_at', $start, (int) ($period === 'today' ? 1 : $period)),
        ];

        $reversals = ($base)()
            ->with(['borrower.user', 'loan.loanProduct', 'allocations'])
            ->where('reversed_at', '>=', $start)
            ->when($method, fn ($q) => $q->where('payment_method', $method))
            ->when($search, fn ($q) => $q->where(function ($n) use ($search) {
                $n->where('mpesa_receipt_number', 'like', "%{$search}%")
                  ->orWhere('external_reference',  'like', "%{$search}%")
                  ->orWhere('reversal_reason',      'like', "%{$search}%")
                  ->orWhereHas('borrower.user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
            }))
            ->latest('reversed_at')
            ->paginate(15)
            ->withQueryString();

        return view('finance.reversals', compact(
            'period', 'search', 'method',
            'stats', 'reversals',
        ));
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Disbursed Loans
    // ──────────────────────────────────────────────────────────────────────────

    public function disbursed(Request $request)
    {
        $orgId   = Auth::user()->organisation_id;
        $period  = $request->string('period', '30')->toString();
        $status  = $request->string('status')->toString();
        $search  = trim($request->string('search')->toString());
        $product = $request->string('product')->toString();

        $start = match ($period) {
            'today' => Carbon::today(),
            '7'     => Carbon::now()->subDays(7)->startOfDay(),
            '90'    => Carbon::now()->subDays(90)->startOfDay(),
            default => Carbon::now()->subDays(30)->startOfDay(),
        };

        // ── Stats ────────────────────────────────────────────────────────────
        $statsBase = fn () => Loan::where('organisation_id', $orgId)
            ->whereNotNull('disbursed_at')
            ->where('disbursed_at', '>=', $start);

        $stats = [
            'total_disbursed_cents' => ($statsBase)()->sum('principal_cents'),
            'total_count'           => ($statsBase)()->count(),
            'active_count'          => ($statsBase)()->where('status', 'active')->count(),
            'overdue_count'         => ($statsBase)()->where('status', 'overdue')->count(),
            'defaulted_count'       => ($statsBase)()->where('status', 'defaulted')->count(),
            'completed_count'       => ($statsBase)()->where('status', 'completed')->count(),
        ];

        // ── Paginated loan list ───────────────────────────────────────────────
        $loans = Loan::where('organisation_id', $orgId)
            ->whereNotNull('disbursed_at')
            ->where('disbursed_at', '>=', $start)
            ->with(['borrower.user', 'loanProduct', 'defaultRecord'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($product, fn ($q) => $q->whereHas('loanProduct', fn ($lp) => $lp->where('code', $product)))
            ->when($search, fn ($q) => $q->where(function ($n) use ($search) {
                $n->where('id', is_numeric($search) ? (int) $search : -1)
                  ->orWhere('repayment_reference', 'like', "%{$search}%")
                  ->orWhereHas('borrower.user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
            }))
            ->latest('disbursed_at')
            ->paginate(20)
            ->withQueryString();

        // ── Filter options ────────────────────────────────────────────────────
        $loanProducts = LoanProduct::where('organisation_id', $orgId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return view('finance.disbursed', compact(
            'period', 'status', 'search', 'product',
            'start', 'stats', 'loans', 'loanProducts',
        ));
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Helpers
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Returns [start, prevStart, prevEnd] Carbon instances for a named period.
     */
    private function periodBounds(string $period): array
    {
        $start = match ($period) {
            'today' => Carbon::today(),
            'week'  => Carbon::now()->subDays(7)->startOfDay(),
            '7'     => Carbon::now()->subDays(7)->startOfDay(),
            '90'    => Carbon::now()->subDays(90)->startOfDay(),
            default => Carbon::now()->startOfMonth(),   // 'month'
        };

        $days      = (int) $start->diffInDays(now()) ?: 1;
        $prevStart = $start->copy()->subDays($days);
        $prevEnd   = $start->copy();

        return [$start, $prevStart, $prevEnd];
    }

    /** Build a 7-bucket sparkline (0–100 normalised) */
    private function buildSparkline($query, string $dateCol, Carbon $from, int $totalDays): array
    {
        $bucketDays = max(1, (int) ceil($totalDays / 7));
        $buckets    = [];
        for ($i = 0; $i < 7; $i++) {
            $s = $from->copy()->addDays($i * $bucketDays);
            $e = $s->copy()->addDays($bucketDays);
            $buckets[] = (clone $query)->where($dateCol, '>=', $s)->where($dateCol, '<', $e)->sum('amount_cents');
        }
        $max = max($buckets) ?: 1;
        return array_map(fn ($v) => max(8, (int) round(($v / $max) * 100)), $buckets);
    }

    /** 14 daily collection buckets for the trend chart */
    private function buildDailyBuckets(int $orgId, int $days): array
    {
        $labels     = [];
        $collected  = [];
        $reconciled = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $day   = Carbon::today()->subDays($i);
            $dayEnd = $day->copy()->endOfDay();

            $base = LoanPayment::where('organisation_id', $orgId)
                ->whereDate('received_at', $day->toDateString());

            $labels[]     = $day->format('d M');
            $collected[]  = (int) ($base->sum('amount_cents') / 100);
            $reconciled[] = (int) (
                LoanPayment::where('organisation_id', $orgId)
                    ->whereDate('received_at', $day->toDateString())
                    ->where('status', 'posted')
                    ->sum('amount_cents') / 100
            );
        }

        return compact('labels', 'collected', 'reconciled');
    }

    /** 12-month rolling buckets */
    private function buildMonthlyBuckets(int $orgId, int $months): array
    {
        $labels     = [];
        $collected  = [];
        $reconciled = [];
        $reversed   = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i)->startOfMonth();
            $end   = $month->copy()->endOfMonth();

            $labels[]     = $month->format('M y');
            $collected[]  = (int) (
                LoanPayment::where('organisation_id', $orgId)
                    ->whereBetween('received_at', [$month, $end])
                    ->sum('amount_cents') / 100
            );
            $reconciled[] = (int) (
                LoanPayment::where('organisation_id', $orgId)
                    ->whereBetween('received_at', [$month, $end])
                    ->where('status', 'posted')
                    ->sum('amount_cents') / 100
            );
            $reversed[] = (int) (
                LoanPayment::where('organisation_id', $orgId)
                    ->where('status', 'reversed')
                    ->whereBetween('reversed_at', [$month, $end])
                    ->sum('amount_cents') / 100
            );
        }

        return compact('labels', 'collected', 'reconciled', 'reversed');
    }
}
