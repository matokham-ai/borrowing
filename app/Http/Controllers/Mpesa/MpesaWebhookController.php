<?php

namespace App\Http\Controllers\Mpesa;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessMpesaWebhook;
use App\Models\Organisation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * MpesaWebhookController
 *
 * Receives inbound Daraja callbacks and dispatches them for background
 * processing.  The controller's only job is to:
 *
 *  1. Identify the organisation from the route slug.
 *  2. Parse + lightly validate the raw JSON body.
 *  3. Dispatch ProcessMpesaWebhook (queued).
 *  4. Return HTTP 200 to Daraja within < 5 seconds.
 *
 * Routes are unauthenticated (Daraja cannot send session cookies).
 * Validation is limited to: valid JSON + known organisation slug.
 * All heavy processing and idempotency happen inside the job.
 *
 * URL structure:
 *   POST /api/pay/{orgSlug}/c2b/confirmation
 *   POST /api/pay/{orgSlug}/c2b/validation
 *   POST /api/pay/{orgSlug}/stk/callback
 *   POST /api/pay/{orgSlug}/b2c/result
 *   POST /api/pay/{orgSlug}/b2c/timeout
 *   POST /api/pay/{orgSlug}/reversal/result
 */
class MpesaWebhookController extends Controller
{
    // ── C2B ───────────────────────────────────────────────────────────────

    /**
     * POST /api/pay/{orgSlug}/c2b/confirmation
     *
     * Called by Daraja when a customer completes a PayBill / Till payment.
     * This is the primary inbound repayment entry point.
     */
    public function c2bConfirmation(Request $request, string $orgSlug): JsonResponse
    {
        return $this->dispatch($request, $orgSlug, 'c2b_confirmation');
    }

    /**
     * POST /api/pay/{orgSlug}/c2b/validation
     *
     * Called by Daraja before a payment is processed (optional hook).
     * Returning ResultCode 0 accepts the payment; non-zero rejects it.
     */
    public function c2bValidation(Request $request, string $orgSlug): JsonResponse
    {
        return $this->dispatch($request, $orgSlug, 'c2b_validation');
    }

    // ── STK Push ──────────────────────────────────────────────────────────

    /**
     * POST /api/pay/{orgSlug}/stk/callback
     *
     * Result of an STK Push prompt — success or failure.
     */
    public function stkCallback(Request $request, string $orgSlug): JsonResponse
    {
        return $this->dispatch($request, $orgSlug, 'stk_callback');
    }

    // ── B2C ───────────────────────────────────────────────────────────────

    /**
     * POST /api/pay/{orgSlug}/b2c/result
     *
     * B2C disbursement result — success or failure.
     */
    public function b2cResult(Request $request, string $orgSlug): JsonResponse
    {
        return $this->dispatch($request, $orgSlug, 'b2c_result');
    }

    /**
     * POST /api/pay/{orgSlug}/b2c/timeout
     *
     * Called by Daraja when a B2C request times out without a result.
     * Treated as a failure — the disbursement service will mark it failed.
     */
    public function b2cTimeout(Request $request, string $orgSlug): JsonResponse
    {
        // Timeouts arrive in the same B2C result format
        return $this->dispatch($request, $orgSlug, 'b2c_result');
    }

    // ── Reversals ─────────────────────────────────────────────────────────

    /**
     * POST /api/pay/{orgSlug}/reversal/result
     *
     * M-Pesa-initiated reversal of a previous transaction.
     */
    public function reversalResult(Request $request, string $orgSlug): JsonResponse
    {
        return $this->dispatch($request, $orgSlug, 'reversal_result');
    }

    // ── Shared dispatcher ─────────────────────────────────────────────────

    /**
     * Validate the organisation, log receipt, dispatch the job, return ack.
     */
    private function dispatch(
        Request $request,
        string  $orgSlug,
        string  $callbackType,
    ): JsonResponse {
        // ── Resolve organisation ──────────────────────────────────────────
        $organisation = Organisation::where('slug', $orgSlug)
            ->where('is_active', true)
            ->first();

        if (!$organisation) {
            Log::warning('MpesaWebhookController: unknown organisation slug', [
                'slug'          => $orgSlug,
                'callback_type' => $callbackType,
                'ip'            => $request->ip(),
            ]);
            // Still return 200 to prevent Daraja from retrying indefinitely
            return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
        }

        // ── Parse body ────────────────────────────────────────────────────
        $payload = $request->json()->all();

        if (empty($payload)) {
            Log::warning('MpesaWebhookController: empty or non-JSON payload', [
                'slug'          => $orgSlug,
                'callback_type' => $callbackType,
            ]);
            return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
        }

        Log::info('MpesaWebhookController: callback received', [
            'org_id'        => $organisation->id,
            'callback_type' => $callbackType,
            'ip'            => $request->ip(),
        ]);

        // ── Queue for processing ──────────────────────────────────────────
        ProcessMpesaWebhook::dispatch(
            callbackType:   $callbackType,
            payload:        $payload,
            organisationId: (int) $organisation->id,
            sourceIp:       $request->ip() ?? '',
        );

        // Daraja requires this exact response shape
        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }
}
