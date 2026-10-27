<?php

namespace App\Services\Mpesa;

use App\Models\Loan;
use App\Models\MpesaTransaction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * MpesaStkService
 *
 * Initiates an STK (Lipa Na M-Pesa Online) Push prompt to a borrower's phone,
 * requesting them to enter their M-Pesa PIN to authorise a payment.
 *
 * Flow
 * ────
 *  1. Generate a timestamp + password (Base64 of shortcode + passkey + timestamp).
 *  2. POST to Daraja STK Push endpoint.
 *  3. Persist an MpesaTransaction record in 'submitted' status.
 *  4. The result arrives asynchronously via the STK callback webhook
 *     (POST /api/pay/{orgSlug}/stk/callback) which is handled by
 *     MpesaCallbackService::handleStkCallback() → ProcessMpesaTransaction.
 *
 * Credentials
 * ───────────
 * Uses the STK-specific shortcode (MPESA_STK_SHORTCODE) + passkey (MPESA_PASSKEY).
 * OAuth token is shared with C2B credentials (MPESA_CONSUMER_KEY / SECRET) and
 * cached for 55 minutes under key mpesa_stk_token_{orgId}.
 *
 * Idempotency
 * ───────────
 * A call for the same (loan_id, amount) within the same minute reuses the
 * existing MpesaTransaction rather than issuing a duplicate prompt.
 */
class MpesaStkService
{
    private const SANDBOX_BASE    = 'https://sandbox.safaricom.co.ke';
    private const PRODUCTION_BASE = 'https://api.safaricom.co.ke';
    private const STK_ENDPOINT    = '/mpesa/stkpush/v1/processrequest';
    private const OAUTH_ENDPOINT  = '/oauth/v1/generate';

    // ── Public API ────────────────────────────────────────────────────────

    /**
     * Send an STK Push prompt to a borrower requesting loan repayment.
     *
     * @param  Loan   $loan          The loan being repaid.
     * @param  int    $amountCents   Amount to request in KES cents.
     * @param  string $description   Short description shown in the M-Pesa prompt (max 13 chars).
     * @return MpesaTransaction      The persisted transaction record.
     *
     * @throws \RuntimeException on configuration or Daraja API failure.
     */
    public function push(Loan $loan, int $amountCents, string $description = 'Loan repayment'): MpesaTransaction
    {
        if ($amountCents <= 0) {
            throw new \InvalidArgumentException('STK Push amount must be greater than zero.');
        }

        $orgId = (int) $loan->organisation_id;

        $phone = DB::table('borrowers')
            ->where('organisation_id', $orgId)
            ->where('id', $loan->borrower_id)
            ->value('phone_number');

        if (empty($phone)) {
            throw new \RuntimeException("Cannot initiate STK Push for loan #{$loan->id}: borrower has no phone number.");
        }

        $amountKes    = (int) ceil($amountCents / 100);   // Daraja requires whole KES, round up
        $shortcode    = config('services.mpesa.stk_shortcode');
        $passkey      = config('services.mpesa.passkey');
        $timestamp    = now()->format('YmdHis');
        $password     = base64_encode($shortcode . $passkey . $timestamp);
        $checkoutRef  = 'STK-' . $orgId . '-' . $loan->id . '-' . $timestamp;

        // Resolve callback URL from config or derive from APP_URL + org slug
        $orgSlug     = DB::table('organisations')->where('id', $orgId)->value('slug') ?? (string) $orgId;
        $callbackUrl = config('services.mpesa.stk_callback_url')
            ?? route('mpesa.stk.callback', ['orgSlug' => $orgSlug], true);

        // ── Idempotency: reuse if a 'submitted' or 'processing' STK exists
        //    for this loan within the last 5 minutes ─────────────────────
        $idempotencyKey = "stk-{$orgId}-{$loan->id}-" . now()->format('YmdHi');  // minute-level

        $existing = MpesaTransaction::where('organisation_id', $orgId)
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($existing) {
            Log::info('MpesaStkService: reusing existing STK transaction', [
                'loan_id' => $loan->id,
                'tx_id'   => $existing->id,
            ]);
            return $existing;
        }

        $token = $this->fetchStkToken($orgId);

        // ── Persist the transaction before sending ────────────────────────
        $mpesaTx = MpesaTransaction::create([
            'organisation_id'            => $orgId,
            'borrower_id'                => $loan->borrower_id,
            'loan_id'                    => $loan->id,
            'transaction_type'           => 'stk_push',
            'direction'                  => 'inbound',
            'status'                     => 'submitted',
            'idempotency_key'            => $idempotencyKey,
            'originator_conversation_id' => $checkoutRef,
            'amount_cents'               => $amountCents,
            'phone_number'               => $phone,
            'account_reference'          => $loan->repayment_reference ?? ('LN-' . str_pad($loan->id, 5, '0', STR_PAD_LEFT)),
            'received_at'                => now(),
        ]);

        // ── Send to Daraja ────────────────────────────────────────────────
        $requestPayload = [
            'BusinessShortCode' => $shortcode,
            'Password'          => $password,
            'Timestamp'         => $timestamp,
            'TransactionType'   => 'CustomerPayBillOnline',
            'Amount'            => $amountKes,
            'PartyA'            => $phone,
            'PartyB'            => $shortcode,
            'PhoneNumber'       => $phone,
            'CallBackURL'       => $callbackUrl,
            'AccountReference'  => substr($loan->repayment_reference ?? ('LN' . $loan->id), 0, 12),
            'TransactionDesc'   => substr($description, 0, 13),
        ];

        try {
            $response = Http::withToken($token)
                ->timeout(30)
                ->post($this->baseUrl() . self::STK_ENDPOINT, $requestPayload);

            $body = $response->json() ?? [];

            // Daraja returns ResponseCode '0' on acceptance (string, not int)
            $accepted = $response->successful()
                && isset($body['ResponseCode'])
                && (string) $body['ResponseCode'] === '0';

            if ($accepted) {
                $mpesaTx->update([
                    'checkout_request_id' => $body['CheckoutRequestID'] ?? null,
                    'status'               => 'processing',
                ]);

                Log::info('MpesaStkService: STK Push sent', [
                    'loan_id'            => $loan->id,
                    'tx_id'              => $mpesaTx->id,
                    'checkout_request_id'=> $body['CheckoutRequestID'] ?? null,
                    'amount_kes'         => $amountKes,
                    'phone'              => substr($phone, 0, 6) . '****',
                ]);
            } else {
                $mpesaTx->update([
                    'status'             => 'failed',
                    'result_description' => $body['errorMessage'] ?? ($body['ResponseDescription'] ?? 'STK Push rejected'),
                ]);

                Log::error('MpesaStkService: Daraja rejected STK Push', [
                    'loan_id'     => $loan->id,
                    'http_status' => $response->status(),
                    'response'    => $body,
                ]);

                throw new \RuntimeException(
                    'Daraja rejected the STK Push request: ' . ($body['errorMessage'] ?? $body['ResponseDescription'] ?? 'Unknown error')
                );
            }
        } catch (\Throwable $e) {
            // Don't double-update if we already set failed above
            if ($mpesaTx->status !== 'failed') {
                $mpesaTx->update([
                    'status'             => 'failed',
                    'result_description' => $e->getMessage(),
                ]);
            }

            Log::error('MpesaStkService: HTTP exception sending STK Push', [
                'loan_id' => $loan->id,
                'error'   => $e->getMessage(),
            ]);

            throw $e;
        }

        return $mpesaTx;
    }

    /**
     * Query the status of a pending STK Push by CheckoutRequestID.
     *
     * Useful for polling when the callback hasn't arrived yet.
     *
     * @param  string $checkoutRequestId  The CheckoutRequestID from the push response.
     * @param  int    $orgId
     * @return array  Raw Daraja response body.
     */
    public function queryStatus(string $checkoutRequestId, int $orgId): array
    {
        $shortcode = config('services.mpesa.stk_shortcode');
        $passkey   = config('services.mpesa.passkey');
        $timestamp = now()->format('YmdHis');
        $password  = base64_encode($shortcode . $passkey . $timestamp);
        $token     = $this->fetchStkToken($orgId);

        $response = Http::withToken($token)
            ->timeout(15)
            ->post($this->baseUrl() . '/mpesa/stkpushquery/v1/query', [
                'BusinessShortCode' => $shortcode,
                'Password'          => $password,
                'Timestamp'         => $timestamp,
                'CheckoutRequestID' => $checkoutRequestId,
            ]);

        return $response->json() ?? [];
    }

    // ── OAuth token ───────────────────────────────────────────────────────

    /**
     * Fetch (or return a cached) STK OAuth token.
     * STK uses the same C2B consumer key/secret, distinct from B2C.
     */
    private function fetchStkToken(int $orgId): string
    {
        $cacheKey = "mpesa_stk_token_{$orgId}";

        return Cache::remember($cacheKey, now()->addMinutes(55), function () {
            $base   = $this->baseUrl();
            $key    = config('services.mpesa.consumer_key');
            $secret = config('services.mpesa.consumer_secret');

            if (empty($key) || empty($secret)) {
                throw new \RuntimeException(
                    'M-Pesa consumer key/secret not configured. Check MPESA_CONSUMER_KEY and MPESA_CONSUMER_SECRET in .env.'
                );
            }

            $response = Http::withBasicAuth($key, $secret)
                ->timeout(15)
                ->get($this->baseUrl() . self::OAUTH_ENDPOINT, ['grant_type' => 'client_credentials']);

            if (!$response->successful()) {
                throw new \RuntimeException(
                    "Daraja STK OAuth failed (HTTP {$response->status()}): " . $response->body()
                );
            }

            $token = $response->json('access_token');

            if (empty($token)) {
                throw new \RuntimeException('Daraja STK OAuth response missing access_token.');
            }

            return $token;
        });
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function baseUrl(): string
    {
        return config('services.mpesa.env', 'sandbox') === 'production'
            ? self::PRODUCTION_BASE
            : self::SANDBOX_BASE;
    }
}
