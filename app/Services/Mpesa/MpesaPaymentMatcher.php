<?php

namespace App\Services\Mpesa;

use App\Models\Borrower;
use App\Models\Loan;
use App\Models\MpesaTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * MpesaPaymentMatcher
 *
 * Matches an inbound mpesa_transaction to a borrower and loan.
 *
 * Matching hierarchy (most precise → least precise)
 * ──────────────────────────────────────────────────
 * 1. account_reference  — borrower quotes the loan repayment reference
 *                         (MAV-XXXXXXXX). Most reliable; used for PayBill/C2B.
 * 2. checkout_request_id — STK Push flows stamp this on the transaction;
 *                          the originating STK request already linked to a loan.
 * 3. phone_number       — fall back to matching on the borrower's registered
 *                          mobile number.  Only valid when the borrower has
 *                          exactly one active/overdue loan.
 * 4. Unmatched          — loan_id and borrower_id remain NULL; transaction
 *                          enters the reconciliation queue.
 *
 * None of these paths throw exceptions — the caller always gets a result
 * object describing what was (or wasn't) matched.
 */
class MpesaPaymentMatcher
{
    // ── Main API ─────────────────────────────────────────────────────────

    /**
     * Attempt to match an MpesaTransaction to a borrower + loan.
     *
     * Returns a plain object with:
     *   matched        bool
     *   match_strategy string|null  ('reference'|'stk'|'phone'|null)
     *   borrower_id    int|null
     *   loan_id        int|null
     *   confidence     string       'high'|'medium'|'low'|'none'
     *   reason         string       human-readable explanation
     */
    public function match(MpesaTransaction $tx): object
    {
        $orgId = (int) $tx->organisation_id;

        // ── Strategy 1: repayment reference in account_reference ─────────
        if (!empty($tx->account_reference)) {
            $result = $this->matchByReference($tx->account_reference, $orgId);
            if ($result->matched || $result->confidence !== 'none') {
                return $result;
            }
        }

        // ── Strategy 2: STK checkout_request_id ──────────────────────────
        if (!empty($tx->checkout_request_id)) {
            $result = $this->matchByStkCheckout($tx->checkout_request_id, $orgId);
            if ($result->matched || $result->confidence !== 'none') {
                return $result;
            }
        }

        // ── Strategy 3: phone number ──────────────────────────────────────
        if (!empty($tx->phone_number)) {
            $result = $this->matchByPhone($tx->phone_number, $orgId);
            // Even if matched=false, return this result if it has useful context
            // (e.g. borrower found but has 0 or multiple active loans).
            if ($result->matched || $result->confidence !== 'none') {
                return $result;
            }
        }

        // ── Unmatched ─────────────────────────────────────────────────────
        return (object) [
            'matched'         => false,
            'match_strategy'  => null,
            'borrower_id'     => null,
            'loan_id'         => null,
            'confidence'      => 'none',
            'reason'          => 'No matching borrower or loan found. Transaction requires manual reconciliation.',
        ];
    }

    // ── Matching strategies ───────────────────────────────────────────────

    /**
     * Match by repayment reference (e.g. MAV-00001245).
     *
     * This is the most reliable strategy: the borrower explicitly quotes the
     * loan reference when paying. We look up the loan directly.
     */
    private function matchByReference(string $ref, int $orgId): object
    {
        $ref  = strtoupper(trim($ref));
        $loan = DB::table('loans')
            ->where('organisation_id', $orgId)
            ->where('repayment_reference', $ref)
            ->whereIn('status', ['active', 'overdue', 'disbursement_pending'])
            ->select('id', 'borrower_id', 'status', 'repayment_reference')
            ->first();

        if (!$loan) {
            // Reference string doesn't match any active loan
            // Could be expired/completed loan paying late, or a typo
            $closedLoan = DB::table('loans')
                ->where('organisation_id', $orgId)
                ->where('repayment_reference', $ref)
                ->select('id', 'borrower_id', 'status')
                ->first();

            if ($closedLoan) {
                return (object) [
                    'matched'        => true,
                    'match_strategy' => 'reference',
                    'borrower_id'    => (int) $closedLoan->borrower_id,
                    'loan_id'        => (int) $closedLoan->id,
                    'confidence'     => 'high',
                    'reason'         => "Matched by repayment reference '{$ref}' to a {$closedLoan->status} loan.",
                ];
            }

            return (object) [
                'matched'        => false,
                'match_strategy' => 'reference',
                'borrower_id'    => null,
                'loan_id'        => null,
                'confidence'     => 'none',
                'reason'         => "Repayment reference '{$ref}' not found.",
            ];
        }

        return (object) [
            'matched'        => true,
            'match_strategy' => 'reference',
            'borrower_id'    => (int) $loan->borrower_id,
            'loan_id'        => (int) $loan->id,
            'confidence'     => 'high',
            'reason'         => "Matched by repayment reference '{$ref}'.",
        ];
    }

    /**
     * Match by STK Push checkout_request_id.
     *
     * When we initiate an STK Push we already know the loan; the
     * checkout_request_id was stored on the originating mpesa_transaction.
     * Find the earlier "submitted" transaction that shares this checkout ID
     * and already has a loan_id.
     */
    private function matchByStkCheckout(string $checkoutId, int $orgId): object
    {
        $prior = DB::table('mpesa_transactions')
            ->where('organisation_id', $orgId)
            ->where(function ($query) use ($checkoutId) {
                $query->where('checkout_request_id', $checkoutId)
                    // Older STK requests stored this identifier in conversation_id.
                    ->orWhere('conversation_id', $checkoutId);
            })
            ->whereNotNull('loan_id')
            ->orderBy('id')
            ->select('borrower_id', 'loan_id')
            ->first();

        if (!$prior) {
            return (object) [
                'matched'        => false,
                'match_strategy' => 'stk',
                'borrower_id'    => null,
                'loan_id'        => null,
                'confidence'     => 'none',
                'reason'         => "No prior STK transaction found for checkout_request_id '{$checkoutId}'.",
            ];
        }

        return (object) [
            'matched'        => true,
            'match_strategy' => 'stk',
            'borrower_id'    => (int) $prior->borrower_id,
            'loan_id'        => (int) $prior->loan_id,
            'confidence'     => 'high',
            'reason'         => "Matched via STK checkout_request_id '{$checkoutId}'.",
        ];
    }

    /**
     * Match by phone number.
     *
     * Only safe when the borrower has exactly one active/overdue loan.
     * If there are zero or multiple active loans the match is ambiguous
     * and we fall back to unmatched (manual reconciliation).
     *
     * Confidence is 'medium' because:
     *   - Someone else may be paying from the same phone.
     *   - The borrower may have multiple obligations (rare but possible).
     */
    private function matchByPhone(string $phone, int $orgId): object
    {
        $borrower = DB::table('borrowers')
            ->where('organisation_id', $orgId)
            ->where('phone_number', $phone)
            ->select('id')
            ->first();

        if (!$borrower) {
            return (object) [
                'matched'        => false,
                'match_strategy' => 'phone',
                'borrower_id'    => null,
                'loan_id'        => null,
                'confidence'     => 'none',
                'reason'         => "No borrower registered with phone {$phone}.",
            ];
        }

        $activeLoans = DB::table('loans')
            ->where('organisation_id', $orgId)
            ->where('borrower_id', $borrower->id)
            ->whereIn('status', ['active', 'overdue'])
            ->select('id', 'status')
            ->get();

        if ($activeLoans->isEmpty()) {
            return (object) [
                'matched'        => false,
                'match_strategy' => 'phone',
                'borrower_id'    => (int) $borrower->id,
                'loan_id'        => null,
                'confidence'     => 'low',
                'reason'         => "Borrower found by phone {$phone} but has no active or overdue loans.",
            ];
        }

        if ($activeLoans->count() > 1) {
            Log::warning('MpesaPaymentMatcher: ambiguous phone match — multiple active loans', [
                'organisation_id' => $orgId,
                'phone'           => $phone,
                'borrower_id'     => $borrower->id,
                'loan_ids'        => $activeLoans->pluck('id')->toArray(),
            ]);

            return (object) [
                'matched'        => false,
                'match_strategy' => 'phone',
                'borrower_id'    => (int) $borrower->id,
                'loan_id'        => null,
                'confidence'     => 'low',
                'reason'         => "Borrower has {$activeLoans->count()} active loans — cannot determine which loan this payment is for.",
            ];
        }

        $loan = $activeLoans->first();

        return (object) [
            'matched'        => true,
            'match_strategy' => 'phone',
            'borrower_id'    => (int) $borrower->id,
            'loan_id'        => (int) $loan->id,
            'confidence'     => 'medium',
            'reason'         => "Matched by phone number {$phone} to a single {$loan->status} loan.",
        ];
    }

    // ── Apply match result to the transaction row ─────────────────────────

    /**
     * Stamp the match result onto the mpesa_transaction record.
     * Called by ProcessMpesaTransaction after matching.
     */
    public function applyMatch(MpesaTransaction $tx, object $matchResult): void
    {
        $updates = [
            'borrower_id' => $matchResult->borrower_id,
            'loan_id'     => $matchResult->loan_id,
        ];

        if (!$matchResult->matched) {
            // Unmatched transactions sit at status='received' for reconciliation
            $updates['status'] = 'received';
        } elseif ($tx->status === 'received') {
            $updates['status'] = 'success';
        }

        $tx->update($updates);
    }
}
