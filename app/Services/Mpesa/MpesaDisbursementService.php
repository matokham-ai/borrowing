<?php

namespace App\Services\Mpesa;

use App\Models\Loan;
use App\Models\MpesaTransaction;
use App\Models\MpesaTransactionAttempt;
use App\Services\Behaviour\BehaviourScoreService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * MpesaDisbursementService
 *
 * Handles the outbound B2C (Business-to-Customer) flow for loan disbursement.
 *
 * Credential source
 * ─────────────────
 * Credentials come from config/services.php (populated from .env).
 * B2C uses its OWN consumer key / secret — different from the C2B / STK
 * credentials.  This matches the real production setup in the don/ folder
 * where params_b2c.php holds separate B2C keys.
 *
 * API endpoint
 * ────────────
 * Uses Daraja B2C v1:  POST /mpesa/b2c/v1/paymentrequest
 * (The don/ folder confirms v1 is the live version in use.)
 *
 * OAuth token caching
 * ───────────────────
 * The B2C access token is cached for 55 minutes (Daraja tokens last 60 min).
 * Cache key: mpesa_b2c_token_{organisationId}
 */
class MpesaDisbursementService
{
    private const SANDBOX_BASE    = 'https://sandbox.safaricom.co.ke';
    private const PRODUCTION_BASE = 'https://api.safaricom.co.ke';

    // B2C API endpoint path — v1 (confirmed from production don/ code)
    private const B2C_ENDPOINT = '/mpesa/b2c/v1/paymentrequest';

    // ── Public API ────────────────────────────────────────────────────────

    /**
     * Initiate a B2C disbursement for a loan in disbursement_pending status.
     *
     * @throws \RuntimeException on configuration or precondition failure.
     */
    public function disburse(Loan $loan): MpesaTransaction
    {
        if ($loan->status !== 'disbursement_pending') {
            throw new \RuntimeException(
                "Cannot disburse: loan #{$loan->id} is in status '{$loan->status}'."
            );
        }

        $orgId = (int) $loan->organisation_id;
        $this->assertReady($orgId);

        // Borrower's registered phone number (Kenya format 2547XXXXXXXX)
        $storedPhone = DB::table('borrowers')
            ->where('organisation_id', $orgId)
            ->where('id', $loan->borrower_id)
            ->value('phone_number');

        if (empty($storedPhone)) {
            throw new \RuntimeException(
                "Cannot disburse loan #{$loan->id}: borrower has no phone number on record."
            );
        }

        $phone = $this->normaliseKenyanPhone((string) $storedPhone);
        $token = $this->fetchB2cToken($orgId);
        $originatorConvId = 'DIS-' . $orgId . '-' . $loan->id . '-' . Str::upper(Str::random(8));

        // ── Create / recover the mpesa_transaction record ─────────────────
        $shouldSubmit = false;
        $mpesaTx = DB::transaction(function () use ($loan, $orgId, $phone, $originatorConvId, &$shouldSubmit) {
            $idempotencyKey = "disburse-{$orgId}-{$loan->id}";

            $existing = MpesaTransaction::where('organisation_id', $orgId)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing) {
                $lastAttempt = MpesaTransactionAttempt::where('mpesa_transaction_id', $existing->id)
                    ->latest('attempt_number')
                    ->first();

                if ($this->isSafeAuthRetry($existing, $lastAttempt)) {
                    $existing->update([
                        'status'                     => 'submitted',
                        'originator_conversation_id' => $originatorConvId,
                        'result_description'         => null,
                    ]);
                    $loan->update(['status' => 'disbursement_pending']);
                    $shouldSubmit = true;

                    return $existing->refresh();
                }

                $shouldSubmit = $lastAttempt === null;
                return $existing;
            }

            $tx = MpesaTransaction::create([
                'organisation_id'            => $orgId,
                'borrower_id'                => $loan->borrower_id,
                'loan_id'                    => $loan->id,
                'transaction_type'           => 'b2c',
                'direction'                  => 'outbound',
                'status'                     => 'submitted',
                'idempotency_key'            => $idempotencyKey,
                'originator_conversation_id' => $originatorConvId,
                'amount_cents'               => $loan->principal_cents,
                'phone_number'               => $phone,
                'account_reference'          => $loan->repayment_reference,
                'received_at'                => now(),
            ]);

            $loan->update(['disbursement_mpesa_transaction_id' => $tx->id]);
            $shouldSubmit = true;

            return $tx;
        });

        if (!$shouldSubmit) {
            return $mpesaTx;
        }

        // ── Send to Daraja ────────────────────────────────────────────────
        $this->sendB2cRequest(
            mpesaTx:          $mpesaTx,
            loan:             $loan,
            token:            $token,
            phone:            $phone,
            originatorConvId: $mpesaTx->originator_conversation_id,
            orgId:            $orgId,
        );

        return $mpesaTx;
    }

    public function canSafelyRetry(Loan $loan): bool
    {
        if (!$loan->disbursement_mpesa_transaction_id) {
            return false;
        }

        $transaction = MpesaTransaction::where('organisation_id', $loan->organisation_id)
            ->find($loan->disbursement_mpesa_transaction_id);

        if (!$transaction || (int) $transaction->loan_id !== (int) $loan->id || $transaction->direction !== 'outbound') {
            return false;
        }

        $lastAttempt = MpesaTransactionAttempt::where('mpesa_transaction_id', $transaction->id)
            ->latest('attempt_number')
            ->first();

        return $this->isSafeAuthRetry($transaction, $lastAttempt);
    }

    private function isSafeAuthRetry(
        MpesaTransaction $transaction,
        ?MpesaTransactionAttempt $attempt
    ): bool {
        return $transaction->status === 'failed'
            && empty($transaction->conversation_id)
            && $attempt !== null
            && $attempt->status === 'failed'
            && in_array((string) $attempt->response_code, ['401', '403'], true);
    }

    /**
     * Validate required B2C settings and callback URLs before creating a loan.
     *
     * @throws \RuntimeException when the live integration is not ready.
     */
    public function assertReady(int $organisationId, ?string $recipientPhone = null): void
    {
        foreach ([
            'b2c_consumer_key'    => 'MPESA_B2C_CONSUMER_KEY',
            'b2c_consumer_secret' => 'MPESA_B2C_CONSUMER_SECRET',
            'b2c_shortcode'       => 'MPESA_B2C_SHORTCODE',
            'initiator_name'      => 'MPESA_INITIATOR_NAME',
            'security_credential' => 'MPESA_SECURITY_CREDENTIAL',
        ] as $setting => $envName) {
            if (empty(config("services.mpesa.{$setting}"))) {
                throw new \RuntimeException("M-Pesa B2C is not configured: set {$envName}.");
            }
        }

        if ($recipientPhone !== null) {
            $this->normaliseKenyanPhone($recipientPhone);
        }

        $orgSlug = DB::table('organisations')
            ->where('id', $organisationId)
            ->value('slug') ?? (string) $organisationId;

        $callbackUrls = [
            'result' => config('services.mpesa.b2c_result_url')
                ? str_replace('{orgSlug}', $orgSlug, config('services.mpesa.b2c_result_url'))
                : url("/api/mpesa/{$orgSlug}/b2c/result"),
            'timeout' => config('services.mpesa.b2c_timeout_url')
                ? str_replace('{orgSlug}', $orgSlug, config('services.mpesa.b2c_timeout_url'))
                : url("/api/mpesa/{$orgSlug}/b2c/timeout"),
        ];

        foreach ($callbackUrls as $type => $callbackUrl) {
            $parts = parse_url($callbackUrl);
            $host = strtolower($parts['host'] ?? '');

            if (
                ($parts['scheme'] ?? '') !== 'https'
                || $host === ''
                || in_array($host, ['localhost', '127.0.0.1', '::1'], true)
                || filter_var($host, FILTER_VALIDATE_IP)
                || str_ends_with($host, '.localhost')
                || str_ends_with($host, '.local')
                || str_ends_with($host, '.test')
            ) {
                $setting = $type === 'result'
                    ? 'MPESA_B2C_RESULT_URL'
                    : 'MPESA_B2C_TIMEOUT_URL';

                throw new \RuntimeException(
                    "M-Pesa B2C {$type} callback must use a publicly reachable HTTPS URL. Set {$setting}."
                );
            }
        }
    }

    /**
     * Handle a successful B2C result callback.
     * Activates the loan: status → active, sets disbursed_at + matures_at.
     */
    public function handleDisbursementSuccess(MpesaTransaction $tx): void
    {
        if (empty($tx->loan_id)) {
            Log::warning('MpesaDisbursementService: B2C success but no loan_id', ['tx_id' => $tx->id]);
            return;
        }

        DB::transaction(function () use ($tx) {
            $loan = Loan::where('organisation_id', $tx->organisation_id)->find($tx->loan_id);

            if (!$loan || !in_array($loan->status, ['disbursement_pending', 'approved'], true)) {
                return;
            }

            $disbursedAt = $tx->transacted_at ?? now();
            $maturesAt   = $disbursedAt->copy()->addDays((int) $loan->term_days);

            $loan->update([
                'status'       => 'active',
                'disbursed_at' => $disbursedAt,
                'matures_at'   => $maturesAt,
            ]);

            $tx->update(['status' => 'success']);

            Log::info('MpesaDisbursementService: loan activated', [
                'loan_id'      => $loan->id,
                'disbursed_at' => $disbursedAt->toIso8601String(),
                'matures_at'   => $maturesAt->toIso8601String(),
            ]);
        });

        $loan = Loan::find($tx->loan_id);
        if ($loan) {
            app(BehaviourScoreService::class)->recordDisbursed($loan);
        }
    }

    /**
     * Handle a failed / timed-out B2C callback.
     * Loan stays in disbursement_pending for operator retry / cancel.
     */
    public function handleDisbursementFailure(MpesaTransaction $tx, string $reason = ''): void
    {
        $tx->update([
            'status'             => 'failed',
            'result_description' => $reason ?: $tx->result_description,
        ]);

        Log::warning('MpesaDisbursementService: B2C disbursement failed', [
            'loan_id' => $tx->loan_id,
            'tx_id'   => $tx->id,
            'reason'  => $reason,
        ]);
    }

    // ── Daraja B2C request ────────────────────────────────────────────────

    private function sendB2cRequest(
        MpesaTransaction $mpesaTx,
        Loan             $loan,
        string           $token,
        string           $phone,
        string           $originatorConvId,
        int              $orgId,
    ): void {
        $base      = $this->baseUrl();
        $amountKes = (int) round($loan->principal_cents / 100);  // cents → whole KES

        // Resolve org slug for callback URL building
        $orgSlug = DB::table('organisations')->where('id', $orgId)->value('slug') ?? (string) $orgId;

        $b2cResultUrl  = config('services.mpesa.b2c_result_url')
            ? str_replace('{orgSlug}', $orgSlug, config('services.mpesa.b2c_result_url'))
            : url("/api/mpesa/{$orgSlug}/b2c/result");
        $b2cTimeoutUrl = config('services.mpesa.b2c_timeout_url')
            ? str_replace('{orgSlug}', $orgSlug, config('services.mpesa.b2c_timeout_url'))
            : url("/api/mpesa/{$orgSlug}/b2c/timeout");

        $requestPayload = [
            'InitiatorName'            => config('services.mpesa.initiator_name'),
            'SecurityCredential'       => config('services.mpesa.security_credential'),
            'CommandID'                => 'BusinessPayment',
            'Amount'                   => $amountKes,
            'PartyA'                   => config('services.mpesa.b2c_shortcode'),
            'PartyB'                   => $phone,
            'Remarks'                  => 'Loan ' . ($loan->repayment_reference ?? $loan->id),
            'QueueTimeOutURL'          => $b2cTimeoutUrl,
            'ResultURL'                => $b2cResultUrl,
            'Occasion'                 => $loan->repayment_reference ?? '',
            'OriginatorConversationID' => $originatorConvId,
        ];

        $attemptNumber = MpesaTransactionAttempt::where('mpesa_transaction_id', $mpesaTx->id)->count() + 1;

        $attempt = MpesaTransactionAttempt::create([
            'organisation_id'      => $orgId,
            'mpesa_transaction_id' => $mpesaTx->id,
            'attempt_number'       => $attemptNumber,
            'status'               => 'submitted',
            'request_reference'    => $originatorConvId,
            'request_payload'      => array_replace($requestPayload, [
                'SecurityCredential' => '[REDACTED]',
            ]),
            'submitted_at'         => now(),
        ]);

        try {
            $response     = Http::withToken($token)
                ->timeout(30)
                ->post($base . self::B2C_ENDPOINT, $requestPayload);

            $responseBody = $response->json() ?? [];

            // Daraja ack contains OriginatorConversationID on success
            $success = $response->successful() && !isset($responseBody['errorCode']);

            $attempt->update([
                'status'               => $success ? 'submitted' : 'failed',
                'response_code'        => (string) $response->status(),
                'response_description' => $responseBody['ResponseDescription'] ?? ($responseBody['errorMessage'] ?? null),
                'response_payload'     => $responseBody,
                'completed_at'         => now(),
            ]);

            if ($success) {
                $mpesaTx->update([
                    'conversation_id' => $responseBody['ConversationID'] ?? null,
                ]);

                Log::info('MpesaDisbursementService: B2C request accepted by Daraja', [
                    'loan_id'          => $loan->id,
                    'originator_conv'  => $originatorConvId,
                    'conversation_id'  => $responseBody['ConversationID'] ?? null,
                ]);
            } else {
                $mpesaTx->update([
                    'status'             => 'failed',
                    'result_description' => $responseBody['errorMessage'] ?? 'B2C request rejected',
                ]);
                $loan->update(['status' => 'disbursement_failed']);

                if ($response->status() === 401) {
                    Cache::forget($this->b2cTokenCacheKey($orgId));
                }

                Log::error('MpesaDisbursementService: Daraja rejected B2C request', [
                    'loan_id'     => $loan->id,
                    'http_status' => $response->status(),
                    'response'    => $responseBody,
                ]);
            }
        } catch (\Throwable $e) {
            $attempt->update([
                'status'               => 'failed',
                'response_description' => $e->getMessage(),
                'completed_at'         => now(),
            ]);

            $mpesaTx->update(['status' => 'failed']);

            Log::error('MpesaDisbursementService: HTTP exception on B2C request', [
                'loan_id' => $loan->id,
                'error'   => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    // ── OAuth token ───────────────────────────────────────────────────────

    /**
     * Fetch a B2C OAuth access token, cached for 55 minutes.
     *
     * B2C uses its own consumer key/secret (separate from C2B/STK).
     * This is confirmed by the don/ code where params_b2c.php holds
     * different credentials from params.php.
     *
     * Pass $force = true to bypass the cache and fetch a fresh token.
     * This is used after a 401 response to guarantee the next attempt
     * does not reuse a stale cached token.
     */
    private function fetchB2cToken(int $orgId, bool $force = false): string
    {
        $cacheKey = $this->b2cTokenCacheKey($orgId);

        if ($force) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, now()->addMinutes(55), function () {
            $base     = $this->baseUrl();
            $key      = config('services.mpesa.b2c_consumer_key');
            $secret   = config('services.mpesa.b2c_consumer_secret');

            if (empty($key) || empty($secret)) {
                throw new \RuntimeException(
                    'M-Pesa B2C consumer key/secret not configured. Check MPESA_B2C_CONSUMER_KEY and MPESA_B2C_CONSUMER_SECRET in .env'
                );
            }

            $response = Http::withBasicAuth($key, $secret)
                ->timeout(15)
                ->get("{$base}/oauth/v1/generate", ['grant_type' => 'client_credentials']);

            if (!$response->successful()) {
                throw new \RuntimeException(
                    "Daraja B2C OAuth failed (HTTP {$response->status()}): " . $response->body()
                );
            }

            $token = $response->json('access_token');

            if (empty($token)) {
                throw new \RuntimeException('Daraja B2C OAuth response missing access_token.');
            }

            return $token;
        });
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function baseUrl(): string
    {
        $env = config('services.mpesa.env', 'sandbox');
        return $env === 'production' ? self::PRODUCTION_BASE : self::SANDBOX_BASE;
    }

    private function b2cTokenCacheKey(int $orgId): string
    {
        return 'mpesa_b2c_token_' . config('services.mpesa.env', 'sandbox') . "_{$orgId}";
    }

    private function normaliseKenyanPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        if (strlen($digits) === 9 && preg_match('/^[17]\d{8}$/', $digits)) {
            $digits = '254' . $digits;
        } elseif (strlen($digits) === 10 && str_starts_with($digits, '0')) {
            $digits = '254' . substr($digits, 1);
        }

        if (!preg_match('/^254[17]\d{8}$/', $digits)) {
            throw new \RuntimeException(
                'Cannot disburse: borrower phone number must be a valid Kenyan mobile number.'
            );
        }

        return $digits;
    }
}
