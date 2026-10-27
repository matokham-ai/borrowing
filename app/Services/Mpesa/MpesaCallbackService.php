<?php

namespace App\Services\Mpesa;

use App\Jobs\ProcessMpesaTransaction;
use App\Models\MpesaTransaction;
use App\Models\MpesaWebhookEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * MpesaCallbackService
 *
 * Handles the very first step of every inbound Daraja webhook:
 *
 *   1. Compute an idempotency key for the raw event.
 *   2. Persist the raw payload to mpesa_webhook_events (idempotent write).
 *   3. Determine whether a matching mpesa_transaction already exists.
 *   4. Create/update mpesa_transaction with the normalised fields.
 *   5. Process B2C results inline; queue other transaction processing.
 *   6. Return an HTTP-200-compatible ack payload.
 *
 * This class is intentionally thin — it does the minimum needed to:
 *   a) Guarantee we never lose a callback.
 *   b) Respond to Daraja within the required window (< 5 s).
 *   c) Hand off non-B2C processing to a queued job.
 *
 * Supported callback types
 * ─────────────────────────
 *   c2b_confirmation   — Customer pays to PayBill/Till (ShortCode 3014281)
 *   c2b_validation     — Pre-payment validation hook
 *   stk_callback       — STK Push result callback (ShortCode 4037237)
 *   b2c_result         — B2C disbursement result (ShortCode 3014281)
 *   reversal_result    — Transaction reversal result
 *
 * Credentials / shortcodes come from config/services.php → .env.
 */
class MpesaCallbackService
{
    // ── Public entry points ──────────────────────────────────────────────

    /**
     * Handle a C2B Confirmation callback.
     *
     * Daraja POSTs this when a customer completes a PayBill / Till payment.
     * This is the primary inbound repayment path.
     *
     * @param  array  $payload  Decoded JSON body from Daraja.
     * @param  int    $organisationId
     * @param  string $sourceIp
     * @return array  Daraja-compatible acknowledgement.
     */
    public function handleC2bConfirmation(array $payload, int $organisationId, string $sourceIp = ''): array
    {
        return $this->ingest(
            eventType:      'c2b_confirmation',
            transactionType:'c2b',
            direction:      'inbound',
            payload:        $payload,
            organisationId: $organisationId,
            sourceIp:       $sourceIp,
            idempotencyKey: $this->c2bIdempotencyKey($payload, $organisationId),
        );
    }

    /**
     * Handle a C2B Validation callback (optional — only if validation URL is registered).
     */
    public function handleC2bValidation(array $payload, int $organisationId, string $sourceIp = ''): array
    {
        // Validation callbacks should not create transactions — just ack.
        // Future: add business-rule checks here and return ResultCode 0 or C2B_REJECT.
        $this->saveWebhookEvent(
            eventType:      'c2b_validation',
            idempotencyKey: $this->c2bIdempotencyKey($payload, $organisationId),
            payload:        $payload,
            organisationId: $organisationId,
            sourceIp:       $sourceIp,
            processingStatus: 'ignored',
        );

        return ['ResultCode' => 0, 'ResultDesc' => 'Accepted'];
    }

    /**
     * Handle an STK Push callback.
     */
    public function handleStkCallback(array $payload, int $organisationId, string $sourceIp = ''): array
    {
        return $this->ingest(
            eventType:      'stk_callback',
            transactionType:'stk_push',
            direction:      'inbound',
            payload:        $payload,
            organisationId: $organisationId,
            sourceIp:       $sourceIp,
            idempotencyKey: $this->stkIdempotencyKey($payload, $organisationId),
        );
    }

    /**
     * Handle a B2C Result callback (disbursement confirmation or failure).
     */
    public function handleB2cResult(array $payload, int $organisationId, string $sourceIp = ''): array
    {
        return $this->ingest(
            eventType:      'b2c_result',
            transactionType:'b2c',
            direction:      'outbound',
            payload:        $payload,
            organisationId: $organisationId,
            sourceIp:       $sourceIp,
            idempotencyKey: $this->b2cIdempotencyKey($payload, $organisationId),
        );
    }

    /**
     * Handle a Reversal Result callback.
     */
    public function handleReversalResult(array $payload, int $organisationId, string $sourceIp = ''): array
    {
        return $this->ingest(
            eventType:      'reversal_result',
            transactionType:'c2b',       // reversals are always on inbound transactions
            direction:      'inbound',
            payload:        $payload,
            organisationId: $organisationId,
            sourceIp:       $sourceIp,
            idempotencyKey: 'rev-' . $organisationId . '-' . ($payload['TransID'] ?? Str::uuid()),
        );
    }

    // ── Core ingestion pipeline ──────────────────────────────────────────

    /**
     * The shared ingestion path for all callback types.
     */
    private function ingest(
        string $eventType,
        string $transactionType,
        string $direction,
        array  $payload,
        int    $organisationId,
        string $sourceIp,
        string $idempotencyKey,
    ): array {
        return DB::transaction(function () use (
            $eventType, $transactionType, $direction,
            $payload, $organisationId, $sourceIp, $idempotencyKey
        ) {
            // ── 1. Save / deduplicate webhook event ──────────────────────
            $webhookEvent = $this->saveWebhookEvent(
                eventType:      $eventType,
                idempotencyKey: $idempotencyKey,
                payload:        $payload,
                organisationId: $organisationId,
                sourceIp:       $sourceIp,
                processingStatus: 'queued',
            );

            // If we already processed this exact event, just ack
            if ($webhookEvent->processing_status === 'processed') {
                Log::info('MpesaCallbackService: duplicate webhook event ignored', [
                    'event_type'      => $eventType,
                    'idempotency_key' => $idempotencyKey,
                    'organisation_id' => $organisationId,
                ]);
                return $this->ack();
            }

            // ── 2. Normalise the payload into a canonical field set ───────
            $normalised = $this->normalise($eventType, $transactionType, $direction, $payload);

            // ── 3. Create or update the mpesa_transaction record ─────────
            $mpesaTx = ($eventType === 'b2c_result')
                ? $this->upsertB2cTransaction(
                    organisationId:  $organisationId,
                    idempotencyKey:  $idempotencyKey,
                    transactionType: $transactionType,
                    direction:       $direction,
                    normalised:      $normalised,
                    rawPayload:      $payload,
                )
                : $this->upsertTransaction(
                    organisationId:  $organisationId,
                    idempotencyKey:  $idempotencyKey,
                    transactionType: $transactionType,
                    direction:       $direction,
                    normalised:      $normalised,
                    rawPayload:      $payload,
                );

            // ── 4. Link webhook event → transaction ──────────────────────
            $webhookEvent->update([
                'mpesa_transaction_id' => $mpesaTx->id,
                'processing_status'    => 'queued',
            ]);

            // B2C results update loan activation and the disbursement receipt.
            // Process these inline so callback handling does not depend on a worker.
            if ($eventType === 'b2c_result') {
                ProcessMpesaTransaction::dispatchSync($mpesaTx->id, $organisationId);
                $webhookEvent->update(['processing_status' => 'processed']);
            } else {
                ProcessMpesaTransaction::dispatch($mpesaTx->id, $organisationId);
            }

            return $this->ack();
        });
    }

    // ── Webhook event persistence ────────────────────────────────────────

    /**
     * Persist the raw webhook event.  Idempotent: if the key already exists
     * the existing row is returned unchanged.
     */
    private function saveWebhookEvent(
        string $eventType,
        string $idempotencyKey,
        array  $payload,
        int    $organisationId,
        string $sourceIp,
        string $processingStatus,
    ): MpesaWebhookEvent {
        // Try to find existing first (idempotency check)
        $existing = MpesaWebhookEvent::where('organisation_id', $organisationId)
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($existing) {
            return $existing;
        }

        return MpesaWebhookEvent::create([
            'organisation_id'  => $organisationId,
            'event_type'       => $eventType,
            'idempotency_key'  => $idempotencyKey,
            'payload'          => $payload,
            'source_ip_address'=> $sourceIp,
            'processing_status'=> $processingStatus,
            'received_at'      => now(),
        ]);
    }

    // ── Transaction upsert ───────────────────────────────────────────────

    /**
     * Create a new mpesa_transaction, or update an existing one if this
     * idempotency key was already received (e.g. Daraja sent the same receipt
     * twice with different metadata).
     */
    private function upsertTransaction(
        int    $organisationId,
        string $idempotencyKey,
        string $transactionType,
        string $direction,
        array  $normalised,
        array  $rawPayload,
    ): MpesaTransaction {
        $existing = MpesaTransaction::where('organisation_id', $organisationId)
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($existing) {
            // Enrich with any fields that arrived in a later callback
            $existing->fill([
                'status'            => $this->resolveStatus($normalised, $existing->status),
                'result_code'       => $normalised['result_code']       ?? $existing->result_code,
                'result_description'=> $normalised['result_description'] ?? $existing->result_description,
                'mpesa_receipt_number' => $normalised['mpesa_receipt_number'] ?? $existing->mpesa_receipt_number,
                'transacted_at'     => $normalised['transacted_at']     ?? $existing->transacted_at,
                'provider_payload'  => $rawPayload,
            ]);
            $existing->save();
            return $existing;
        }

        return MpesaTransaction::create([
            'organisation_id'              => $organisationId,
            'transaction_type'             => $transactionType,
            'direction'                    => $direction,
            'status'                       => $this->resolveStatus($normalised, 'received'),
            'idempotency_key'              => $idempotencyKey,
            'mpesa_receipt_number'         => $normalised['mpesa_receipt_number'] ?? null,
            'merchant_request_id'          => $normalised['merchant_request_id'] ?? null,
            'checkout_request_id'          => $normalised['checkout_request_id'] ?? null,
            'originator_conversation_id'   => $normalised['originator_conversation_id'] ?? null,
            'conversation_id'              => $normalised['conversation_id'] ?? null,
            'amount_cents'                 => $normalised['amount_cents'] ?? null,
            'phone_number'                 => $normalised['phone_number'] ?? null,
            'sender_name'                  => $normalised['sender_name'] ?? null,
            'receiver_name'                => $normalised['receiver_name'] ?? null,
            'account_reference'            => $normalised['account_reference'] ?? null,
            'result_code'                  => $normalised['result_code'] ?? null,
            'result_description'           => $normalised['result_description'] ?? null,
            'transacted_at'                => $normalised['transacted_at'] ?? null,
            'provider_payload'             => $rawPayload,
            'received_at'                  => now(),
        ]);
    }

    /**
     * B2C-specific upsert: first try to correlate back to the originating
     * disbursement record via originator_conversation_id / conversation_id,
     * so that loan_id and borrower_id are preserved on the callback.
     * Falls back to the generic idempotency-key upsert if no match is found.
     */
    private function upsertB2cTransaction(
        int    $organisationId,
        string $idempotencyKey,
        string $transactionType,
        string $direction,
        array  $normalised,
        array  $rawPayload,
    ): MpesaTransaction {
        // ── 1. Try to find the originating disbursement record ───────────
        $originatorConvId = $normalised['originator_conversation_id'] ?? null;
        $convId           = $normalised['conversation_id'] ?? null;

        $existing = null;

        if ($originatorConvId) {
            $existing = MpesaTransaction::where('organisation_id', $organisationId)
                ->where('direction', 'outbound')
                ->where('originator_conversation_id', $originatorConvId)
                ->first();
        }

        if (!$existing && $convId) {
            $existing = MpesaTransaction::where('organisation_id', $organisationId)
                ->where('direction', 'outbound')
                ->where('conversation_id', $convId)
                ->first();
        }

        if ($existing) {
            // Update in-place — PRESERVE loan_id, borrower_id, amount_cents,
            // idempotency_key, direction. Only update callback-supplied fields.
            $existing->fill([
                'status'               => $this->resolveStatus($normalised, $existing->status),
                'result_code'          => $normalised['result_code']          ?? $existing->result_code,
                'result_description'   => $normalised['result_description']   ?? $existing->result_description,
                'mpesa_receipt_number' => $normalised['mpesa_receipt_number'] ?? $existing->mpesa_receipt_number,
                'conversation_id'      => $normalised['conversation_id']      ?? $existing->conversation_id,
                'transacted_at'        => $normalised['transacted_at']        ?? $existing->transacted_at,
                'provider_payload'     => $rawPayload,
            ]);
            $existing->save();
            return $existing;
        }

        // ── 2. No originating disbursement found — create orphan row ─────
        Log::warning('MpesaCallbackService: B2C result callback has no matching disbursement record', [
            'organisation_id'            => $organisationId,
            'originator_conversation_id' => $originatorConvId,
            'conversation_id'            => $convId,
            'fallback_idempotency_key'   => $idempotencyKey,
        ]);

        return $this->upsertTransaction(
            organisationId:  $organisationId,
            idempotencyKey:  $idempotencyKey,
            transactionType: $transactionType,
            direction:       $direction,
            normalised:      $normalised,
            rawPayload:      $rawPayload,
        );
    }

    // ── Payload normalisation ────────────────────────────────────────────

    /**
     * Extract canonical fields from the raw Daraja payload.
     * Each event type has a slightly different structure.
     */
    private function normalise(
        string $eventType,
        string $transactionType,
        string $direction,
        array  $payload,
    ): array {
        return match ($eventType) {
            'c2b_confirmation', 'c2b_validation' => $this->normaliseC2b($payload),
            'stk_callback'                        => $this->normaliseStk($payload),
            'b2c_result'                          => $this->normaliseB2c($payload),
            'reversal_result'                     => $this->normaliseReversal($payload),
            default                               => [],
        };
    }

    /**
     * Normalise a C2B confirmation payload.
     *
     * Daraja C2B confirmation body:
     * {
     *   "TransactionType": "Pay Bill",
     *   "TransID": "OJI7GU4SRK",
     *   "TransTime": "20240101120000",
     *   "TransAmount": "1000.00",
     *   "BusinessShortCode": "174379",
     *   "BillRefNumber": "MAV-00001245",
     *   "InvoiceNumber": "",
     *   "OrgAccountBalance": "12345.00",
     *   "ThirdPartyTransID": "",
     *   "MSISDN": "254712345678",
     *   "FirstName": "John",
     *   "MiddleName": "",
     *   "LastName": "Doe"
     * }
     */
    private function normaliseC2b(array $p): array
    {
        $amountRaw = (float) ($p['TransAmount'] ?? 0);
        $balanceRaw = (float) ($p['OrgAccountBalance'] ?? 0);

        return [
            'mpesa_receipt_number'      => $p['TransID'] ?? null,
            'amount_cents'              => (int) round($amountRaw * 100),
            'org_account_balance_cents' => $balanceRaw > 0 ? (int) round($balanceRaw * 100) : null,
            'phone_number'         => $this->normalisePhone($p['MSISDN'] ?? ''),
            'sender_name'          => trim(implode(' ', array_filter([
                $p['FirstName'] ?? '',
                $p['MiddleName'] ?? '',
                $p['LastName'] ?? '',
            ]))),
            'account_reference'    => $p['BillRefNumber'] ?? null,
            'transacted_at'        => $this->parseTransTime($p['TransTime'] ?? ''),
            'result_code'          => '0',
            'result_description'   => 'Success',
        ];
    }

    /**
     * Normalise an STK Push callback payload.
     *
     * {
     *   "Body": {
     *     "stkCallback": {
     *       "MerchantRequestID": "29115-34620561-1",
     *       "CheckoutRequestID": "ws_CO_191220191020363925",
     *       "ResultCode": 0,
     *       "ResultDesc": "The service request is processed successfully.",
     *       "CallbackMetadata": {
     *         "Item": [
     *           {"Name":"Amount","Value":1000},
     *           {"Name":"MpesaReceiptNumber","Value":"NLJ7RT61SV"},
     *           {"Name":"TransactionDate","Value":20191219102115},
     *           {"Name":"PhoneNumber","Value":254708374149}
     *         ]
     *       }
     *     }
     *   }
     * }
     */
    private function normaliseStk(array $p): array
    {
        $cb = $p['Body']['stkCallback'] ?? $p;

        $resultCode = (int) ($cb['ResultCode'] ?? -1);
        $meta       = $this->stkMetaItems($cb['CallbackMetadata']['Item'] ?? []);

        $amountRaw = (float) ($meta['Amount'] ?? 0);

        return [
            'merchant_request_id'  => $cb['MerchantRequestID'] ?? null,
            'checkout_request_id'  => $cb['CheckoutRequestID'] ?? null,
            'mpesa_receipt_number' => $meta['MpesaReceiptNumber'] ?? null,
            'amount_cents'         => (int) round($amountRaw * 100),
            'phone_number'         => $this->normalisePhone((string) ($meta['PhoneNumber'] ?? '')),
            'transacted_at'        => $this->parseTransTime((string) ($meta['TransactionDate'] ?? '')),
            'result_code'          => (string) $resultCode,
            'result_description'   => $cb['ResultDesc'] ?? null,
        ];
    }

    /**
     * Normalise a B2C Result callback.
     *
     * {
     *   "Result": {
     *     "ResultType": 0,
     *     "ResultCode": 0,
     *     "ResultDesc": "The service request is processed successfully.",
     *     "OriginatorConversationID": "19455-424535-1",
     *     "ConversationID": "AG_20191219_00005797af5d7d75f652",
     *     "TransactionID": "NLJ0000000",
     *     "ResultParameters": {
     *       "ResultParameter": [
     *         {"Key":"TransactionAmount","Value":1000},
     *         {"Key":"TransactionReceipt","Value":"NLJ0000000"},
     *         {"Key":"ReceiverPartyPublicName","Value":"254708374149 - John Doe"},
     *         {"Key":"TransactionCompletedDateTime","Value":"19.12.2019 10:24:07"},
     *         {"Key":"B2CUtilityAccountAvailableFunds","Value":10116},
     *         {"Key":"B2CWorkingAccountAvailableFunds","Value":0}
     *       ]
     *     }
     *   }
     * }
     */
    private function normaliseB2c(array $p): array
    {
        $result = $p['Result'] ?? $p;
        $params = $this->b2cResultParams($result['ResultParameters']['ResultParameter'] ?? []);

        $amountRaw = (float) ($params['TransactionAmount'] ?? 0);
        $receiver  = $params['ReceiverPartyPublicName'] ?? '';
        $phone     = $this->normalisePhone(explode(' ', $receiver)[0] ?? '');

        return [
            'originator_conversation_id' => $result['OriginatorConversationID'] ?? null,
            'conversation_id'            => $result['ConversationID'] ?? null,
            'mpesa_receipt_number'       => $result['TransactionID'] ?? ($params['TransactionReceipt'] ?? null),
            'amount_cents'               => (int) round($amountRaw * 100),
            'phone_number'               => $phone,
            'receiver_name'              => $receiver,
            'result_code'                => (string) ($result['ResultCode'] ?? -1),
            'result_description'         => $result['ResultDesc'] ?? null,
            'transacted_at'              => $this->parseB2cDate($params['TransactionCompletedDateTime'] ?? ''),
        ];
    }

    /**
     * Normalise a Reversal Result callback.
     */
    private function normaliseReversal(array $p): array
    {
        $result = $p['Result'] ?? $p;

        return [
            'originator_conversation_id' => $result['OriginatorConversationID'] ?? null,
            'conversation_id'            => $result['ConversationID'] ?? null,
            'mpesa_receipt_number'       => $result['TransactionID'] ?? null,
            'result_code'                => (string) ($result['ResultCode'] ?? -1),
            'result_description'         => $result['ResultDesc'] ?? null,
        ];
    }

    // ── Idempotency key builders ──────────────────────────────────────────

    private function c2bIdempotencyKey(array $p, int $orgId): string
    {
        $transId = $p['TransID'] ?? Str::uuid();
        return "c2b-{$orgId}-{$transId}";
    }

    private function stkIdempotencyKey(array $p, int $orgId): string
    {
        $cb      = $p['Body']['stkCallback'] ?? $p;
        $checkId = $cb['CheckoutRequestID'] ?? Str::uuid();
        return "stk-{$orgId}-{$checkId}";
    }

    private function b2cIdempotencyKey(array $p, int $orgId): string
    {
        $result  = $p['Result'] ?? $p;
        $convId  = $result['ConversationID'] ?? ($result['OriginatorConversationID'] ?? Str::uuid());
        return "b2c-{$orgId}-{$convId}";
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    /**
     * Derive the transaction status from normalised result code.
     * For C2B the code is always '0' (we only receive confirmations).
     * For STK/B2C a non-zero code means a provider-side failure.
     */
    private function resolveStatus(array $normalised, string $current): string
    {
        $code = $normalised['result_code'] ?? null;

        if ($code === null) {
            return $current;
        }

        return ((int) $code === 0) ? 'success' : 'failed';
    }

    /** Convert STK callback metadata items array into a flat key→value map. */
    private function stkMetaItems(array $items): array
    {
        $map = [];
        foreach ($items as $item) {
            if (isset($item['Name'])) {
                $map[$item['Name']] = $item['Value'] ?? null;
            }
        }
        return $map;
    }

    /** Convert B2C ResultParameter array into a flat key→value map. */
    private function b2cResultParams(array $items): array
    {
        $map = [];
        foreach ($items as $item) {
            if (isset($item['Key'])) {
                $map[$item['Key']] = $item['Value'] ?? null;
            }
        }
        return $map;
    }

    /**
     * Parse Daraja's compact timestamp format: YYYYMMDDHHmmss → Carbon
     */
    private function parseTransTime(string $raw): ?\Carbon\Carbon
    {
        $raw = preg_replace('/\D/', '', $raw);
        if (strlen($raw) < 14) {
            return null;
        }
        try {
            return \Carbon\Carbon::createFromFormat('YmdHis', substr($raw, 0, 14));
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Parse B2C "DD.MM.YYYY HH:ii:ss" timestamp format.
     */
    private function parseB2cDate(string $raw): ?\Carbon\Carbon
    {
        if (empty($raw)) {
            return null;
        }
        try {
            return \Carbon\Carbon::createFromFormat('d.m.Y H:i:s', $raw);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Normalise phone numbers to the 254XXXXXXXXX format.
     * Accepts: 07XXXXXXXX, +254XXXXXXXX, 254XXXXXXXX.
     */
    private function normalisePhone(string $phone): ?string
    {
        $phone = preg_replace('/\D/', '', $phone);

        if (empty($phone)) {
            return null;
        }

        // Strip leading +
        $phone = ltrim($phone, '+');

        // 07XXXXXXXX → 2547XXXXXXXX
        if (str_starts_with($phone, '0') && strlen($phone) === 10) {
            $phone = '254' . substr($phone, 1);
        }

        return strlen($phone) >= 12 ? $phone : null;
    }

    /** Standard Daraja acknowledgement payload. */
    private function ack(): array
    {
        return ['ResultCode' => 0, 'ResultDesc' => 'Accepted'];
    }
}
