<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\AllocateLoanPayment;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\MpesaTransaction;
use App\Models\User;
use App\Services\Mpesa\MpesaDarajaOperationsService;
use App\Services\Payments\PaymentReversalService;
use App\Services\Payments\PaymentService;
use App\Support\Auditor;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function index(Request $request, PaymentService $paymentService)
    {
        $organisationId = Auth::user()->organisation_id;
        $this->syncUnmatchedPayments($organisationId, $paymentService);
        $status = $request->string('status')->toString();
        $method = $request->string('method')->toString();
        $search = trim($request->string('search')->toString());
        $period = $request->string('period', '30')->toString();
        $view = $request->string('view')->toString();

        if ($view === 'mpesa-stk') {
            return redirect()->route('mpesa.stk.index', $request->query());
        }

        $viewMeta = [
            '' => ['title' => 'Payments', 'eyebrow' => 'COLLECTIONS OPERATIONS', 'description' => 'Keep every repayment visible, reconciled and moving to the right loan.'],
            'incoming' => ['title' => 'Incoming payments', 'eyebrow' => 'CASH INTAKE', 'description' => 'Monitor every repayment received through the collection channels.'],
            'unmatched' => ['title' => 'Unmatched payments', 'eyebrow' => 'EXCEPTIONS QUEUE', 'description' => 'Link unidentified receipts to the correct borrower and loan.'],
            'allocations' => ['title' => 'Payment allocations', 'eyebrow' => 'REPAYMENT WATERFALL', 'description' => 'See how each posted payment was applied across loan schedules.'],
            'reversals' => ['title' => 'Payment reversals', 'eyebrow' => 'AUDIT HISTORY', 'description' => 'Review payments removed from active balances and why.'],
            'reconciliation' => ['title' => 'Reconciliation', 'eyebrow' => 'ACTION CENTER', 'description' => 'Resolve payments waiting for review before they affect loan balances.'],
        ][$view] ?? ['title' => 'Payments', 'eyebrow' => 'COLLECTIONS OPERATIONS', 'description' => 'Keep every repayment visible, reconciled and moving to the right loan.'];

        $viewFilters = [
            'incoming' => ['incoming' => true],
            'unmatched' => ['unmatched' => true],
            'allocations' => ['allocations' => true],
            'reversals' => ['status' => 'reversed'],
            'reconciliation' => ['reconciliation' => true],
            'mpesa-transactions' => ['method' => 'mpesa'],
            'mpesa-stk' => ['method' => 'mpesa', 'mpesa_type' => 'stk_push'],
            'mpesa-b2c' => ['mpesa_type' => 'b2c'],
            'mpesa-c2b' => ['method' => 'mpesa', 'mpesa_type' => 'c2b'],
            'mpesa-failed' => ['status' => 'failed'],
            'mpesa-webhooks' => ['mpesa_type' => 'webhook'],
        ][$view] ?? [];

        $status = $status ?: ($viewFilters['status'] ?? '');
        $method = $method ?: ($viewFilters['method'] ?? '');

        $baseQuery = LoanPayment::where('organisation_id', $organisationId);
        $periodStart = match ($period) {
            'today' => Carbon::today(),
            '7' => Carbon::now()->subDays(7)->startOfDay(),
            '90' => Carbon::now()->subDays(90)->startOfDay(),
            default => Carbon::now()->subDays(30)->startOfDay(),
        };

        $stats = [
            'received' => (clone $baseQuery)->where('received_at', '>=', $periodStart)->sum('amount_cents'),
            'posted' => (clone $baseQuery)->where('status', 'posted')->where('received_at', '>=', $periodStart)->sum('amount_cents'),
            'pending' => (clone $baseQuery)->whereIn('status', ['pending', 'processing'])->count(),
            'unapplied' => (clone $baseQuery)->where('unapplied_cents', '>', 0)->sum('unapplied_cents'),
        ];

        $methodTotals = (clone $baseQuery)
            ->where('received_at', '>=', $periodStart)
            ->selectRaw('payment_method, SUM(amount_cents) as total_cents')
            ->groupBy('payment_method')
            ->pluck('total_cents', 'payment_method');
        $methodMix = collect(['mpesa' => 'M-Pesa', 'bank_transfer' => 'Bank transfer', 'cash' => 'Cash', 'credit_balance' => 'Credit balance'])
            ->map(fn ($label, $key) => [
                'label' => $label,
                'key' => $key,
                'cents' => (int) ($methodTotals[$key] ?? 0),
            ])
            ->filter(fn ($item) => $item['cents'] > 0)
            ->values();

        $payments = (clone $baseQuery)
            ->with(['borrower.user', 'loan.loanProduct', 'loan.borrower.user', 'allocations.loanSchedule', 'mpesaTransaction'])
            ->when($viewFilters['incoming'] ?? false, fn ($query) => $query->whereNotNull('received_at')->where('status', '!=', 'reversed'))
            ->when($viewFilters['unmatched'] ?? false, fn ($query) => $query->whereNull('loan_id'))
            ->when($viewFilters['allocations'] ?? false, fn ($query) => $query->whereHas('allocations'))
            ->when($viewFilters['reconciliation'] ?? false, fn ($query) => $query->whereIn('status', ['pending', 'processing']))
            ->when($viewFilters['mpesa_type'] ?? null, function ($query, $mpesaType) {
                if ($mpesaType === 'webhook') {
                    return $query->whereNotNull('provider_payload');
                }

                return $query->whereHas('mpesaTransaction', function ($transaction) use ($mpesaType) {
                    $transaction->where('transaction_type', $mpesaType);
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($method, fn ($query) => $query->where('payment_method', $method))
            ->when($search, function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('mpesa_receipt_number', 'like', "%{$search}%")
                        ->orWhere('external_reference', 'like', "%{$search}%")
                        ->orWhereHas('borrower.user', fn ($user) => $user->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('loan', fn ($loan) => $loan->where('id', $search));
                });
            })
            ->latest('received_at')
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        return view('payments.index', compact('payments', 'stats', 'status', 'method', 'search', 'period', 'methodMix', 'view', 'viewMeta'));
    }

    public function reversals(Request $request)
    {
        $organisationId = Auth::user()->organisation_id;

        $search       = trim($request->string('search')->toString());
        $period       = $request->string('period', '30')->toString();
        $filterStatus = $request->string('status')->toString();
        $filterMethod = $request->string('method')->toString();
        $filterUser   = $request->integer('user_filter');

        // ── Period bounds ────────────────────────────────────────────────
        $periodStart = match ($period) {
            'today' => Carbon::today(),
            '7'     => Carbon::now()->subDays(7)->startOfDay(),
            '90'    => Carbon::now()->subDays(90)->startOfDay(),
            default => Carbon::now()->subDays(30)->startOfDay(),
        };

        // For date-range navigation arrows
        $prevPeriod = match ($period) {
            'today' => '7',
            '7'     => '30',
            '30'    => '90',
            default => '90',
        };
        $nextPeriod = match ($period) {
            '90'    => '30',
            '30'    => '7',
            '7'     => 'today',
            default => 'today',
        };

        // ── Base query: all reversed payments for this org ───────────────
        $base = LoanPayment::where('organisation_id', $organisationId)
            ->where('status', 'reversed');

        // ── Aggregate stats ──────────────────────────────────────────────
        $periodBase = (clone $base)->where('reversed_at', '>=', $periodStart);

        $totalCents     = (clone $periodBase)->sum('amount_cents');
        $completedCents = (clone $periodBase)->where('status', 'reversed')->sum('amount_cents');
        $pendingCents   = 0;   // reversed payments are terminal; placeholder for pending reversals
        $failedCents    = 0;

        // Rough delta vs previous same-length window
        $windowDays = match ($period) {
            'today' => 1, '7' => 7, '90' => 90, default => 30,
        };
        $prevWindowStart = $periodStart->copy()->subDays($windowDays);
        $prevTotal = (clone $base)
            ->where('reversed_at', '>=', $prevWindowStart)
            ->where('reversed_at', '<', $periodStart)
            ->sum('amount_cents');
        $deltaPct = $prevTotal > 0
            ? (int) round((($totalCents - $prevTotal) / $prevTotal) * 100)
            : ($totalCents > 0 ? 100 : 0);

        $completedPct = $totalCents > 0
            ? (int) round(($completedCents / $totalCents) * 100)
            : 0;

        // Sparkline: 7 buckets, each = ($windowDays / 7) days
        $sparkline          = $this->buildSparkline($base, 'reversed_at', $periodStart, $windowDays);
        $completedSparkline = $sparkline;   // same set (all reversed = completed)

        $stats = [
            'total_cents'        => $totalCents,
            'completed_cents'    => $completedCents,
            'pending_cents'      => $pendingCents,
            'failed_cents'       => $failedCents,
            'delta_pct'          => $deltaPct,
            'completed_pct'      => $completedPct,
            'sparkline'          => $sparkline,
            'completed_sparkline'=> $completedSparkline,
        ];

        // ── Per-tab counts ───────────────────────────────────────────────
        $statusCounts = [
            'completed' => (clone $periodBase)->where('status', 'reversed')->count(),
            'pending'   => 0,
            'failed'    => 0,
        ];

        // ── Paginated results ────────────────────────────────────────────
        $reversals = (clone $base)
            ->with(['borrower.user', 'loan.loanProduct', 'loan.borrower.user', 'allocations'])
            ->where('reversed_at', '>=', $periodStart)
            ->when($filterMethod, fn ($q) => $q->where('payment_method', $filterMethod))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($n) use ($search) {
                    $n->where('mpesa_receipt_number', 'like', "%{$search}%")
                      ->orWhere('external_reference', 'like', "%{$search}%")
                      ->orWhere('reversal_reason', 'like', "%{$search}%")
                      ->orWhereHas('borrower.user', fn ($u) => $u->where('name', 'like', "%{$search}%"))
                      ->orWhereHas('loan', fn ($l) => $l->where('id', $search));
                });
            })
            ->latest('reversed_at')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        // ── Admin users for filter dropdown ──────────────────────────────
        $adminUsers = User::where('organisation_id', $organisationId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('payments.reversals', compact(
            'reversals',
            'stats',
            'statusCounts',
            'search',
            'period',
            'prevPeriod',
            'nextPeriod',
            'filterStatus',
            'filterMethod',
            'filterUser',
            'adminUsers',
        ));
    }

    /**
     * Build a 7-bucket sparkline (heights as percentages 0–100).
     */
    private function buildSparkline($query, string $dateCol, Carbon $from, int $totalDays): array
    {
        $bucketDays = max(1, (int) ceil($totalDays / 7));
        $buckets    = [];

        for ($i = 0; $i < 7; $i++) {
            $start = $from->copy()->addDays($i * $bucketDays);
            $end   = $start->copy()->addDays($bucketDays);
            $buckets[] = (clone $query)
                ->where($dateCol, '>=', $start)
                ->where($dateCol, '<', $end)
                ->sum('amount_cents');
        }

        $max = max($buckets) ?: 1;
        return array_map(fn ($v) => (int) round(($v / $max) * 100) ?: 8, $buckets);
    }

    public function create()
    {
        return view('payments.form', ['payment' => null, 'loans' => $this->loanOptions()]);
    }

    public function store(Request $request, PaymentService $paymentService)
    {
        $data = $request->validate([
            'loan_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_method' => ['required', 'in:cash,bank_transfer,mpesa,credit_balance'],
            'external_reference' => ['nullable', 'string', 'max:100'],
            'received_at' => ['required', 'date'],
        ]);
        $loan = $this->loanQuery()->findOrFail($data['loan_id']);
        $payment = $paymentService->createManual(
            (int) auth()->user()->organisation_id,
            $loan->id,
            $loan->borrower_id,
            (int) round($data['amount'] * 100),
            $data['payment_method'],
            $data['external_reference'] ?? '',
        );
        $payment->update(['received_at' => $data['received_at']]);
        AllocateLoanPayment::dispatchSync($payment->id, (int) auth()->user()->organisation_id);

        return redirect()->route('payments.index')->with('success', 'Payment recorded and allocated.');
    }

    public function edit(LoanPayment $payment)
    {
        $this->ownedPayment($payment);

        return view('payments.form', ['payment' => $payment, 'loans' => $this->loanOptions()]);
    }

    public function update(Request $request, LoanPayment $payment)
    {
        $this->ownedPayment($payment);
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_method' => ['required', 'in:cash,bank_transfer,mpesa,credit_balance'],
            'external_reference' => ['nullable', 'string', 'max:100'],
            'received_at' => ['required', 'date'],
        ]);
        if ($payment->allocations()->exists() && (int) round($data['amount'] * 100) !== $payment->amount_cents) {
            return back()->withInput()->withErrors(['amount' => 'Allocated payments cannot change amount.']);
        }
        $payment->update([
            'amount_cents' => (int) round($data['amount'] * 100),
            'payment_method' => $data['payment_method'],
            'external_reference' => $data['external_reference'] ?? null,
            'received_at' => $data['received_at'],
        ]);

        return redirect()->route('payments.index')->with('success', 'Payment updated.');
    }

    public function match(Request $request, LoanPayment $payment)
    {
        $this->ownedPayment($payment);
        $data = $request->validate(['loan_id' => ['required', 'integer']]);
        $loan = $this->loanQuery()->findOrFail($data['loan_id']);

        if ($payment->allocations()->exists() && (int) $payment->loan_id !== (int) $loan->id) {
            return back()->withErrors(['loan_id' => 'Allocated payments cannot be moved to another loan. Unmatch it before allocation if correction is required.']);
        }

        $payment->update([
            'loan_id' => $loan->id,
            'borrower_id' => $loan->borrower_id,
            'status' => 'posted',
            'unapplied_cents' => 0,
            'posted_at' => now(),
        ]);
        if ($payment->mpesa_transaction_id) {
            MpesaTransaction::where('organisation_id', $payment->organisation_id)
                ->whereKey($payment->mpesa_transaction_id)
                ->update(['loan_id' => $loan->id, 'borrower_id' => $loan->borrower_id, 'status' => 'success']);
        }
        AllocateLoanPayment::dispatchSync($payment->id, (int) auth()->user()->organisation_id);

        Auditor::log('payment.matched', $payment, [
            'new' => ['loan_id' => $loan->id, 'status' => 'posted'],
        ]);

        return redirect()->route('payments.index', ['view' => 'unmatched'])->with('success', 'Payment matched and allocated.');
    }

    public function unmatch(LoanPayment $payment)
    {
        $this->ownedPayment($payment);
        if ($payment->allocations()->exists()) {
            return back()->withErrors(['payment' => 'This payment already has allocations and cannot be unlinked safely.']);
        }

        $payment->update([
            'loan_id' => null,
            'borrower_id' => null,
            'status' => 'pending',
            'unapplied_cents' => $payment->amount_cents,
            'posted_at' => null,
        ]);
        if ($payment->mpesa_transaction_id) {
            MpesaTransaction::where('organisation_id', $payment->organisation_id)
                ->whereKey($payment->mpesa_transaction_id)
                ->update([
                    'loan_id' => null,
                    'borrower_id' => null,
                    'status' => 'received',
                    'processed_at' => null,
                    'reconciled_at' => null,
                ]);
        }

        return redirect()->route('payments.edit', $payment)->with('success', 'Payment is unmatched and ready to link to a loan.');
    }

    public function receipt(LoanPayment $payment)
    {
        $this->ownedPayment($payment);
        $payment->load([
            'borrower.user',
            'loan.loanProduct',
            'loan.borrower.user',
            'allocations',
            'mpesaTransaction',
        ]);

        $organisation = \App\Models\Organisation::find($payment->organisation_id);
        $borrower     = $payment->borrower ?? $payment->loan?->borrower;
        $loan         = $payment->loan;

        // Group allocations by type
        $allocGroups = $payment->allocations
            ->groupBy('allocation_type')
            ->map(fn ($rows) => $rows->sum('amount_cents'));

        $totalAllocated = $allocGroups->sum();

        // Outstanding loan balance
        $outstandingCents = null;
        if ($loan) {
            $paidPrincipal = $loan->payments()
                ->where('status', 'posted')
                ->join('payment_allocations', 'loan_payments.id', '=', 'payment_allocations.loan_payment_id')
                ->where('payment_allocations.allocation_type', 'principal')
                ->sum('payment_allocations.amount_cents');
            $paidInterest = $loan->payments()
                ->where('status', 'posted')
                ->join('payment_allocations', 'loan_payments.id', '=', 'payment_allocations.loan_payment_id')
                ->where('payment_allocations.allocation_type', 'interest')
                ->sum('payment_allocations.amount_cents');
            $outstandingCents = max(0, ($loan->total_payable_cents ?? 0) - $paidPrincipal - $paidInterest);
        }

        return view('payments.receipt', compact(
            'payment',
            'borrower',
            'loan',
            'organisation',
            'allocGroups',
            'totalAllocated',
            'outstandingCents'
        ));
    }

    public function show(LoanPayment $payment)
    {
        $this->ownedPayment($payment);
        $payment->load(['borrower.user', 'loan.loanProduct', 'loan.borrower.user', 'allocations', 'mpesaTransaction']);

        $borrower   = $payment->borrower ?? $payment->loan?->borrower;
        $name       = $borrower?->user?->name ?? 'Unmatched payment';
        $loan       = $payment->loan;
        $ref        = $payment->mpesa_receipt_number ?: $payment->external_reference ?: '—';
        $method     = match ($payment->payment_method) {
            'mpesa'          => 'M-Pesa',
            'bank_transfer'  => 'Bank transfer',
            'cash'           => 'Cash',
            'credit_balance' => 'Credit balance',
            default          => ucfirst($payment->payment_method),
        };

        // Build allocation breakdown grouped by type
        $allocationGroups = $payment->allocations
            ->groupBy('allocation_type')
            ->map(fn ($rows) => $rows->sum('amount_cents'));

        $totalAllocated = $allocationGroups->sum();

        // Waterfall order: penalties → fees → interest → principal → future instalments → excess
        $waterfallOrder = [
            'penalty'    => ['label' => 'Penalties & Fees',      'icon' => 'bi-exclamation-circle'],
            'fees'       => ['label' => 'Fees',                  'icon' => 'bi-receipt'],
            'interest'   => ['label' => 'Interest',              'icon' => 'bi-percent'],
            'principal'  => ['label' => 'Principal',             'icon' => 'bi-coin'],
            'other'      => ['label' => 'Future Instalments',    'icon' => 'bi-calendar2-check'],
        ];

        // Outstanding loan balance (total payable minus sum of posted payment allocations)
        $outstandingCents = null;
        if ($loan) {
            $paidPrincipal = $loan->payments()
                ->where('status', 'posted')
                ->join('payment_allocations', 'loan_payments.id', '=', 'payment_allocations.loan_payment_id')
                ->where('payment_allocations.allocation_type', 'principal')
                ->sum('payment_allocations.amount_cents');
            $paidInterest = $loan->payments()
                ->where('status', 'posted')
                ->join('payment_allocations', 'loan_payments.id', '=', 'payment_allocations.loan_payment_id')
                ->where('payment_allocations.allocation_type', 'interest')
                ->sum('payment_allocations.amount_cents');
            $outstandingCents = max(0, ($loan->total_payable_cents ?? 0) - $paidPrincipal - $paidInterest);
        }

        return response()->json([
            'id'               => $payment->id,
            'name'             => $name,
            'initial'          => strtoupper(substr($name, 0, 1)),
            'loan_ref'         => $loan ? 'Loan #' . str_pad($loan->id, 7, '0', STR_PAD_LEFT) : null,
            'loan_product'     => $loan?->loanProduct?->name,
            'loan_term'        => $loan ? ($loan->term_days . ' days') : null,
            'outstanding_cents'=> $outstandingCents,
            'edit_url'         => route('payments.edit', $payment),
            'receipt_url'      => route('payments.receipt', $payment),
            'reference'        => $ref,
            'method'           => $method,
            'amount_cents'     => $payment->amount_cents,
            'unapplied_cents'  => $payment->unapplied_cents,
            'status'           => $payment->status,
            'received_at'      => $payment->received_at?->format('d M Y, H:i'),
            'posted_at'        => $payment->posted_at?->format('d M Y, H:i'),
            'reversed_at'      => $payment->reversed_at?->format('d M Y, H:i'),
            'reversal_reason'  => $payment->reversal_reason,
            'processed_by'     => 'System',
            'allocations'      => $allocationGroups->map(fn ($cents, $type) => [
                'type'       => ucfirst($type),
                'amount'     => 'KSh ' . number_format($cents / 100, 2),
                'cents'      => $cents,
                'percentage' => $totalAllocated > 0 ? round(($cents / $totalAllocated) * 100, 2) : 0,
                'color'      => match ($type) {
                    'interest'   => '#f79009',
                    'principal'  => '#5b5ce2',
                    'penalty'    => '#f04438',
                    'fees'       => '#9e77ed',
                    default      => '#12b76a',
                },
            ])->values(),
            'total_allocated'  => 'KSh ' . number_format($totalAllocated / 100, 2),
            'total_allocated_cents' => $totalAllocated,
            'waterfall'        => collect($waterfallOrder)->map(fn ($meta, $type) => [
                'step'    => $meta['label'],
                'icon'    => $meta['icon'],
                'applied' => $allocationGroups->has($type)
                    ? 'KSh ' . number_format(($allocationGroups[$type] ?? 0) / 100, 2) . ' applied'
                    : 'No amount applied',
                'has_amount' => $allocationGroups->has($type) && ($allocationGroups[$type] ?? 0) > 0,
            ])->values(),
            'excess_unapplied' => 'KSh ' . number_format(($payment->unapplied_cents ?? 0) / 100, 2),
        ]);
    }

    public function allocate(LoanPayment $payment)
    {
        $this->ownedPayment($payment);
        if ($payment->status !== 'posted' || !$payment->loan_id) {
            return back()->withErrors(['payment' => 'Only posted payments linked to a loan can be allocated.']);
        }

        AllocateLoanPayment::dispatchSync($payment->id, (int) auth()->user()->organisation_id);

        return back()->with('success', 'Payment allocation refreshed.');
    }

    public function reverse(
        Request $request,
        LoanPayment $payment,
        PaymentReversalService $reversalService,
        MpesaDarajaOperationsService $daraja,
    )
    {
        $this->ownedPayment($payment);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);
        $reason = $data['reason'] ?? 'Manual administrator reversal';

        if ($payment->status === 'reversed') {
            try {
                $reversalService->reversePayment($payment, $reason);
            } catch (\Throwable $exception) {
                return back()->withErrors(['payment' => $exception->getMessage()]);
            }

            return redirect()->route('payments.reversals')->with('success', 'Payment is already reversed.');
        }

        if ($payment->payment_method === 'mpesa') {
            try {
                $operation = $daraja->submitPaymentReversal($payment, $reason);
            } catch (\Throwable $exception) {
                return back()->withErrors(['payment' => $exception->getMessage()]);
            }

            Auditor::log('payment.reversal_requested', $payment, [
                'new' => [
                    'status' => 'awaiting_provider',
                    'reason' => $reason,
                    'daraja_operation_id' => $operation->id,
                ],
            ]);

            return redirect()->route('payments.reversals')
                ->with('success', 'Reversal request submitted to Safaricom. The payment remains active until Safaricom confirms the result.');
        }

        try {
            $reversalService->reversePayment($payment, $reason);
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['payment' => $exception->getMessage()]);
        }

        Auditor::log('payment.reversed', $payment, [
            'new' => ['status' => 'reversed', 'reason' => $reason],
        ]);

        return redirect()->route('payments.reversals')->with('success', 'Payment reversed and retained in audit history.');
    }

    public function destroy(LoanPayment $payment, PaymentReversalService $reversalService)
    {
        $this->ownedPayment($payment);
        if ($payment->status === 'posted') {
            $reversalService->reversePayment($payment, 'Deleted by administrator');
        } elseif (!$payment->allocations()->exists()) {
            if ($payment->mpesa_transaction_id) {
                MpesaTransaction::whereKey($payment->mpesa_transaction_id)->update(['loan_payment_id' => null]);
            }
            $payment->delete();
        } else {
            return back()->withErrors(['payment' => 'This payment cannot be deleted after allocation.']);
        }

        return redirect()->route('payments.index')->with('success', 'Payment removed from active records.');
    }

    private function ownedPayment(LoanPayment $payment): void
    {
        abort_unless((int) $payment->organisation_id === (int) auth()->user()->organisation_id, 404);
    }

    private function loanQuery()
    {
        return Loan::where('organisation_id', auth()->user()->organisation_id)
            ->whereIn('status', ['active', 'overdue', 'disbursement_pending'])
            ->with(['borrower.user', 'loanProduct']);
    }

    private function loanOptions()
    {
        return $this->loanQuery()->latest('id')->get();
    }

    private function syncUnmatchedPayments(int $organisationId, PaymentService $paymentService): void
    {
        MpesaTransaction::where('organisation_id', $organisationId)
            ->where('direction', 'inbound')
            ->whereIn('status', ['success', 'received'])
            ->whereNull('loan_payment_id')
            ->get()
            ->each(fn (MpesaTransaction $transaction) => $paymentService->createUnmatchedFromTransaction($transaction));
    }
}
