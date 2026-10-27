<?php

namespace App\Http\Controllers\Mpesa;

use App\Http\Controllers\Controller;
use App\Models\Borrower;
use App\Models\Loan;
use App\Models\MpesaTransaction;
use App\Models\MpesaSetting;
use App\Services\Mpesa\MpesaStkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * MpesaStkController
 *
 * Admin UI for monitoring and managing M-Pesa STK Push transactions.
 *
 * Routes:
 *   GET  /admin/mpesa/stk               → index()   – Main STK dashboard page
 *   POST /admin/mpesa/stk/sync          → sync()    – Trigger Daraja query sync
 *   GET  /admin/mpesa/stk/{tx}          → show()    – JSON detail for drawer
 */
class MpesaStkController extends Controller
{
    public function create()
    {
        $loans = Loan::where('organisation_id', Auth::user()->organisation_id)
            ->whereIn('status', ['active', 'disbursed'])
            ->with(['borrower.user', 'loanProduct'])
            ->latest('id')
            ->get();

        return view('payments.stk-form', compact('loans'));
    }

    public function store(Request $request, MpesaStkService $stkService): RedirectResponse
    {
        $data = $request->validate([
            'loan_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'description' => ['nullable', 'string', 'max:13'],
        ]);

        $loan = Loan::where('organisation_id', Auth::user()->organisation_id)
            ->whereIn('status', ['active', 'disbursed'])
            ->findOrFail($data['loan_id']);

        try {
            $stkService->push(
                $loan,
                (int) round((float) $data['amount'] * 100),
                $data['description'] ?: 'Loan repayment',
            );
        } catch (\Throwable $exception) {
            return back()->withInput()->withErrors(['amount' => $exception->getMessage()]);
        }

        return redirect()->route('mpesa.stk.index')->with('sync_message', 'STK Push sent to the borrower.');
    }

    // ── Index ─────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $organisationId = Auth::user()->organisation_id;

        // ── Inputs ────────────────────────────────────────────────────────
        $search    = trim($request->string('search')->toString());
        $tab       = $request->string('tab', 'all')->toString();           // all | successful | pending | failed | reversed
        $borrowerId = $request->integer('borrower_id') ?: null;
        $statusFilter = $request->string('status')->toString();
        $dateFrom  = $request->string('from')->toString();
        $dateTo    = $request->string('to')->toString();

        // Map tab → status filter (tabs are shortcuts)
        $tabStatus = match ($tab) {
            'successful' => 'success',
            'pending'    => 'processing',
            'failed'     => 'failed',
            'reversed'   => 'reversed',
            default      => null,
        };

        // ── Base query: STK Push transactions only ────────────────────────
        $base = MpesaTransaction::where('organisation_id', $organisationId)
            ->where('transaction_type', 'stk_push');

        // ── Stats (before filters) ────────────────────────────────────────
        // "Today" date range for the stat cards
        $todayStart     = Carbon::today();
        $yesterdayStart = Carbon::yesterday();
        $todayBase      = (clone $base)->where('received_at', '>=', $todayStart);
        $yesterdayBase  = (clone $base)->whereBetween('received_at', [$yesterdayStart, $todayStart]);

        $receivedToday      = (clone $todayBase)->sum('amount_cents');
        $receivedTodayCount = (clone $todayBase)->count();

        $receivedYesterday = (clone $yesterdayBase)->sum('amount_cents');

        // Day-over-day growth percentage (null when no yesterday baseline)
        $receivedGrowth = null;
        if ($receivedYesterday > 0) {
            $receivedGrowth = round((($receivedToday - $receivedYesterday) / $receivedYesterday) * 100);
        }

        $successfulToday      = (clone $todayBase)->where('status', 'success')->sum('amount_cents');
        $successfulTodayCount = (clone $todayBase)->where('status', 'success')->count();

        $pendingAmount = (clone $base)->whereIn('status', ['submitted', 'processing'])->sum('amount_cents');
        $pendingCount  = (clone $base)->whereIn('status', ['submitted', 'processing'])->count();

        $failedAmount = (clone $base)->where('received_at', '>=', $todayStart)
            ->whereIn('status', ['failed', 'reversed'])->sum('amount_cents');
        $failedCount  = (clone $base)->where('received_at', '>=', $todayStart)
            ->whereIn('status', ['failed', 'reversed'])->count();

        // Success rate (today)
        $successRate = $receivedTodayCount > 0
            ? round(($successfulTodayCount / $receivedTodayCount) * 100)
            : 0;

        $failedRate = $receivedTodayCount > 0
            ? round(($failedCount / $receivedTodayCount) * 100)
            : 0;

        // Tab counts (all time)
        $tabCounts = [
            'all'        => (clone $base)->count(),
            'successful' => (clone $base)->where('status', 'success')->count(),
            'pending'    => (clone $base)->whereIn('status', ['submitted', 'processing'])->count(),
            'failed'     => (clone $base)->where('status', 'failed')->count(),
            'reversed'   => (clone $base)->where('status', 'reversed')->count(),
        ];

        // ── Apply filters ─────────────────────────────────────────────────
        $query = (clone $base)
            ->with(['borrower.user', 'loan', 'loanPayment'])
            ->when($tabStatus, fn ($q) => $q->where('status', $tabStatus))
            ->when($statusFilter, fn ($q) => $q->where('status', $statusFilter))
            ->when($borrowerId, fn ($q) => $q->where('borrower_id', $borrowerId))
            ->when($dateFrom, fn ($q) => $q->where('received_at', '>=', Carbon::parse($dateFrom)->startOfDay()))
            ->when($dateTo, fn ($q) => $q->where('received_at', '<=', Carbon::parse($dateTo)->endOfDay()))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($nested) use ($search) {
                    $nested->where('mpesa_receipt_number', 'like', "%{$search}%")
                        ->orWhere('phone_number', 'like', "%{$search}%")
                        ->orWhere('checkout_request_id', 'like', "%{$search}%")
                        ->orWhere('account_reference', 'like', "%{$search}%")
                        ->orWhereHas('borrower.user', fn ($u) => $u->where('name', 'like', "%{$search}%"))
                        ->orWhere(function ($q2) use ($search) {
                            if (is_numeric($search)) {
                                $q2->where('loan_id', (int) $search);
                            }
                        });
                });
            })
            ->latest('received_at')
            ->latest('id');

        $transactions = $query->paginate(15)->withQueryString();

        // ── Borrowers list for the filter dropdown ─────────────────────────
        $borrowers = Borrower::where('organisation_id', $organisationId)
            ->with('user:id,name')
            ->orderBy('id')
            ->get(['id', 'user_id']);

        // ── M-Pesa integration status ─────────────────────────────────────
        $mpesaSetting = MpesaSetting::where('organisation_id', $organisationId)->first();
        $isLive       = $mpesaSetting?->is_active && $mpesaSetting?->environment === 'production';

        return view('payments.stk', compact(
            'transactions',
            'tab',
            'tabCounts',
            'search',
            'borrowerId',
            'borrowers',
            'statusFilter',
            'dateFrom',
            'dateTo',
            'receivedToday',
            'receivedTodayCount',
            'receivedGrowth',
            'successfulToday',
            'successfulTodayCount',
            'pendingAmount',
            'pendingCount',
            'failedAmount',
            'failedCount',
            'successRate',
            'failedRate',
            'isLive',
            'mpesaSetting',
        ));
    }

    // ── Show (JSON for drawer) ────────────────────────────────────────────

    public function show(MpesaTransaction $transaction): JsonResponse
    {
        $organisationId = Auth::user()->organisation_id;

        // Scope to current org
        if ($transaction->organisation_id !== $organisationId
            || $transaction->transaction_type !== 'stk_push') {
            abort(403);
        }

        $transaction->load(['borrower.user', 'loan.loanProduct', 'loanPayment.allocations.loanSchedule']);

        $borrower = $transaction->borrower;
        $name     = $borrower?->user?->name ?? 'Unknown borrower';
        $phone    = $transaction->phone_number ?? $borrower?->phone_number ?? '—';

        $loan     = $transaction->loan;
        $loanRef  = $loan ? 'Loan #' . str_pad($loan->id, 7, '0', STR_PAD_LEFT) : null;

        $payment  = $transaction->loanPayment;

        return response()->json([
            'id'              => $transaction->id,
            'name'            => $name,
            'initial'         => strtoupper(substr($name, 0, 1)),
            'phone'           => $this->formatPhone($phone),
            'status'          => $transaction->status,
            'amount_cents'    => $transaction->amount_cents ?? 0,
            'mpesa_ref'       => $transaction->mpesa_receipt_number ?? '—',
            'checkout_id'     => $transaction->checkout_request_id ?? '—',
            'account_ref'     => $transaction->account_reference ?? '—',
            'received_at'     => $transaction->received_at?->format('d M Y, H:i') ?? '—',
            'transacted_at'   => $transaction->transacted_at?->format('d M Y, H:i') ?? '—',
            'result_code'     => $transaction->result_code ?? '—',
            'result_desc'     => $transaction->result_description ?? '—',
            'loan_ref'        => $loanRef,
            'loan_product'    => $loan?->loanProduct?->name,
            'loan_id'         => $loan?->id,
            'has_payment'     => $payment !== null,
            'payment_id'      => $payment?->id,
            'payment_status'  => $payment?->status,
            'payment_amount'  => $payment?->amount_cents ?? 0,
            'unapplied_cents' => $payment?->unapplied_cents ?? 0,
        ]);
    }

    public function edit(MpesaTransaction $transaction)
    {
        $this->ownedStk($transaction);
        $transaction->load(['borrower.user', 'loan.loanProduct']);

        return view('payments.stk-edit', compact('transaction'));
    }

    public function update(Request $request, MpesaTransaction $transaction): RedirectResponse
    {
        $this->ownedStk($transaction);
        $data = $request->validate([
            'phone_number' => ['nullable', 'string', 'max:30'],
            'account_reference' => ['nullable', 'string', 'max:100'],
            'result_description' => ['nullable', 'string', 'max:500'],
        ]);

        $transaction->update($data);

        return redirect()->route('mpesa.stk.index')->with('sync_message', 'STK transaction updated.');
    }

    public function destroy(MpesaTransaction $transaction): RedirectResponse
    {
        $this->ownedStk($transaction);

        if ($transaction->loan_payment_id || !in_array($transaction->status, ['failed', 'reversed'], true)) {
            return back()->withErrors(['transaction' => 'Only failed or reversed STK transactions without a payment can be removed.']);
        }

        $transaction->delete();

        return redirect()->route('mpesa.stk.index')->with('sync_message', 'STK transaction removed.');
    }

    // ── Sync ─────────────────────────────────────────────────────────────

    /**
     * Sync pending STK transactions by querying their status from Daraja.
     * Targets 'submitted' / 'processing' records that are older than 1 minute
     * and have a checkout_request_id.
     */
    public function sync(Request $request): RedirectResponse
    {
        $organisationId = Auth::user()->organisation_id;

        $pending = MpesaTransaction::where('organisation_id', $organisationId)
            ->where('transaction_type', 'stk_push')
            ->whereIn('status', ['submitted', 'processing'])
            ->whereNotNull('checkout_request_id')
            ->where('received_at', '<=', now()->subMinute())
            ->limit(20)
            ->get();

        $synced = 0;
        foreach ($pending as $tx) {
            try {
                $result = $this->queryStkStatus($tx->checkout_request_id, $organisationId);

                // ResultCode 0 = success, 1032 = cancelled, anything else = failed
                $resultCode = (int) ($result['ResultCode'] ?? -1);
                $newStatus  = match (true) {
                    $resultCode === 0    => 'success',
                    $resultCode === 1032 => 'failed',
                    $resultCode > 0      => 'failed',
                    default              => $tx->status,
                };

                if ($newStatus !== $tx->status) {
                    $tx->update([
                        'status'             => $newStatus,
                        'result_code'        => (string) $resultCode,
                        'result_description' => $result['ResultDesc'] ?? null,
                        'processed_at'       => now(),
                    ]);
                    $synced++;
                }
            } catch (\Throwable $e) {
                Log::warning('MpesaStkController: sync query failed', [
                    'tx_id' => $tx->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $message = $synced > 0
            ? "Synced {$synced} transaction(s) successfully."
            : ($pending->count() > 0
                ? 'No status changes detected.'
                : 'No pending transactions to sync.');

        return redirect()->route('mpesa.stk.index')
            ->with('sync_message', $message);
    }

    // ── Private helpers ───────────────────────────────────────────────────

    /**
     * Query the status of an STK Push transaction from Daraja.
     */
    private function queryStkStatus(string $checkoutRequestId, int $orgId): array
    {
        $shortcode = config('services.mpesa.stk_shortcode');
        $passkey   = config('services.mpesa.passkey');
        $timestamp = now()->format('YmdHis');
        $password  = base64_encode($shortcode . $passkey . $timestamp);
        $token     = $this->fetchStkToken($orgId);

        $base = config('services.mpesa.env', 'sandbox') === 'production'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';

        $response = Http::withToken($token)
            ->timeout(15)
            ->post($base . '/mpesa/stkpushquery/v1/query', [
                'BusinessShortCode' => $shortcode,
                'Password'          => $password,
                'Timestamp'         => $timestamp,
                'CheckoutRequestID' => $checkoutRequestId,
            ]);

        return $response->json() ?? [];
    }

    /**
     * Fetch (or return a cached) STK OAuth token.
     */
    private function fetchStkToken(int $orgId): string
    {
        $cacheKey = "mpesa_stk_token_{$orgId}";

        return Cache::remember($cacheKey, now()->addMinutes(55), function () {
            $base   = config('services.mpesa.env', 'sandbox') === 'production'
                ? 'https://api.safaricom.co.ke'
                : 'https://sandbox.safaricom.co.ke';
            $key    = config('services.mpesa.consumer_key');
            $secret = config('services.mpesa.consumer_secret');

            $response = Http::withBasicAuth($key, $secret)
                ->timeout(15)
                ->get($base . '/oauth/v1/generate', ['grant_type' => 'client_credentials']);

            if (!$response->successful()) {
                throw new \RuntimeException('Daraja STK OAuth failed: ' . $response->body());
            }

            return $response->json('access_token') ?? throw new \RuntimeException('Missing access_token');
        });
    }

    /**
     * Format a raw phone number to display-friendly format: 0712 345 678
     */
    private function formatPhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone);
        if (str_starts_with($phone, '254') && strlen($phone) === 12) {
            $local = '0' . substr($phone, 3);
            return substr($local, 0, 4) . ' ' . substr($local, 4, 3) . ' ' . substr($local, 7);
        }
        return $phone;
    }

    private function ownedStk(MpesaTransaction $transaction): void
    {
        if ($transaction->organisation_id !== Auth::user()->organisation_id
            || $transaction->transaction_type !== 'stk_push') {
            abort(404);
        }
    }
}
