<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * SearchController
 *
 * Global search across borrowers, loans, and payments.
 * Returns JSON results consumed by the topnav search input via fetch().
 *
 * GET /search?q=...
 * Returns up to 5 results per category (borrowers, loans, payments).
 */
class SearchController extends Controller
{
    private const MAX_PER_GROUP = 5;

    public function __invoke(Request $request): JsonResponse
    {
        $q     = trim((string) $request->input('q', ''));
        $orgId = (int) Auth::user()->organisation_id;

        if (mb_strlen($q) < 2) {
            return response()->json(['results' => [], 'query' => $q]);
        }

        $results = [];

        // ── Borrowers ─────────────────────────────────────────────────────
        $borrowers = DB::table('borrowers')
            ->join('users', 'users.id', '=', 'borrowers.user_id')
            ->where('borrowers.organisation_id', $orgId)
            ->where(function ($query) use ($q) {
                $query->where('users.name', 'like', "%{$q}%")
                      ->orWhere('users.email', 'like', "%{$q}%")
                      ->orWhere('borrowers.phone_number', 'like', "%{$q}%")
                      ->orWhere('borrowers.id', is_numeric($q) ? (int) $q : -1);
            })
            ->select('borrowers.id', 'users.name', 'users.email', 'borrowers.phone_number', 'borrowers.account_status')
            ->limit(self::MAX_PER_GROUP)
            ->get();

        foreach ($borrowers as $b) {
            $results[] = [
                'type'     => 'borrower',
                'icon'     => 'bi-person',
                'label'    => $b->name,
                'sub'      => $b->phone_number . ($b->email ? " · {$b->email}" : ''),
                'badge'    => ucfirst($b->account_status),
                'badge_class' => $b->account_status === 'active' ? 'success' : 'warning',
                'url'      => route('borrowers.show', $b->id),
            ];
        }

        // ── Loans ─────────────────────────────────────────────────────────
        $loans = DB::table('loans')
            ->join('borrowers', 'borrowers.id', '=', 'loans.borrower_id')
            ->join('users', 'users.id', '=', 'borrowers.user_id')
            ->join('loan_products', 'loan_products.id', '=', 'loans.loan_product_id')
            ->where('loans.organisation_id', $orgId)
            ->where(function ($query) use ($q) {
                $query->where('loans.repayment_reference', 'like', "%{$q}%")
                      ->orWhere('loans.id', is_numeric($q) ? (int) $q : -1)
                      ->orWhere('users.name', 'like', "%{$q}%")
                      ->orWhere('loan_products.name', 'like', "%{$q}%");
            })
            ->select(
                'loans.id', 'loans.status', 'loans.repayment_reference',
                'loan_products.name as product_name', 'users.name as borrower_name'
            )
            ->limit(self::MAX_PER_GROUP)
            ->get();

        foreach ($loans as $l) {
            $ref = $l->repayment_reference ?? ('LN-' . str_pad($l->id, 5, '0', STR_PAD_LEFT));
            $results[] = [
                'type'        => 'loan',
                'icon'        => 'bi-bank',
                'label'       => $ref . ' · ' . $l->borrower_name,
                'sub'         => $l->product_name,
                'badge'       => ucfirst($l->status),
                'badge_class' => match($l->status) {
                    'active'    => 'success',
                    'overdue'   => 'warning',
                    'defaulted', 'written_off' => 'danger',
                    'completed' => 'info',
                    default     => 'secondary',
                },
                'url' => route('applications.index', ['search' => $ref]),
            ];
        }

        // ── Payments ──────────────────────────────────────────────────────
        $payments = DB::table('loan_payments')
            ->join('borrowers', 'borrowers.id', '=', 'loan_payments.borrower_id')
            ->join('users', 'users.id', '=', 'borrowers.user_id')
            ->where('loan_payments.organisation_id', $orgId)
            ->where(function ($query) use ($q) {
                $query->where('loan_payments.id', is_numeric($q) ? (int) $q : -1)
                      ->orWhere('users.name', 'like', "%{$q}%")
                      ->orWhere('loan_payments.payment_method', 'like', "%{$q}%");
            })
            ->select(
                'loan_payments.id', 'loan_payments.amount_cents',
                'loan_payments.status', 'loan_payments.payment_method',
                'users.name as borrower_name', 'loan_payments.received_at'
            )
            ->latest('loan_payments.received_at')
            ->limit(self::MAX_PER_GROUP)
            ->get();

        foreach ($payments as $p) {
            $results[] = [
                'type'        => 'payment',
                'icon'        => 'bi-arrow-left-right',
                'label'       => 'KES ' . number_format($p->amount_cents / 100, 2) . ' · ' . $p->borrower_name,
                'sub'         => ucfirst(str_replace('_', ' ', $p->payment_method)) . ' · ' . ($p->received_at ? \Illuminate\Support\Carbon::parse($p->received_at)->format('d M Y') : ''),
                'badge'       => ucfirst($p->status),
                'badge_class' => $p->status === 'posted' ? 'success' : ($p->status === 'reversed' ? 'danger' : 'secondary'),
                'url'         => route('payments.show', $p->id),
            ];
        }

        return response()->json([
            'query'   => $q,
            'results' => $results,
            'total'   => count($results),
        ]);
    }
}
