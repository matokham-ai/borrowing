<?php

namespace App\Services\Mpesa;

use App\Models\LoanPayment;
use App\Models\MpesaApiOperation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class MpesaDarajaOperationsService
{
    private const PRODUCTION_BASE = 'https://api.safaricom.co.ke';

    private const SANDBOX_BASE = 'https://sandbox.safaricom.co.ke';

    /**
     * Endpoint definitions are allowlisted here; request data can never choose a URL.
     *
     * @return array<string, array<string, mixed>>
     */
    public function catalog(): array
    {
        return [
            'reversal' => [
                'label' => 'Reversal',
                'path' => '/mpesa/reversal/v1/request',
                'credential_set' => 'reversal',
                'async' => true,
                'required' => ['TransactionID', 'Amount', 'Remarks'],
                'system_fields' => ['Initiator', 'SecurityCredential', 'CommandID', 'ReceiverParty', 'RecieverIdentifierType', 'QueueTimeOutURL', 'ResultURL'],
                'command' => 'TransactionReversal',
            ],
            'c2b_v2_register' => [
                'label' => 'C2B v2 URL registration',
                'path' => '/mpesa/c2b/v2/registerurl',
                'credential_set' => 'c2b',
                'async' => false,
                'required' => ['ShortCode', 'ResponseType'],
                'system_fields' => ['ConfirmationURL', 'ValidationURL'],
            ],
            'c2b_v1_register' => [
                'label' => 'C2B PayBill URL registration',
                'path' => '/mpesa/c2b/v1/registerurl',
                'credential_set' => 'c2b',
                'async' => false,
                'required' => ['ShortCode', 'ResponseType'],
                'system_fields' => ['ConfirmationURL', 'ValidationURL'],
            ],
            'transaction_status' => [
                'label' => 'Transaction status',
                'path' => '/mpesa/transactionstatus/v1/query',
                'credential_set' => 'operations',
                'async' => true,
                'required' => ['TransactionID', 'PartyA', 'IdentifierType', 'Remarks'],
                'system_fields' => ['Initiator', 'SecurityCredential', 'CommandID', 'QueueTimeOutURL', 'ResultURL'],
                'command' => 'TransactionStatusQuery',
            ],
            'account_balance' => [
                'label' => 'Account balance',
                'path' => '/mpesa/accountbalance/v1/query',
                'credential_set' => 'operations',
                'async' => true,
                'required' => ['PartyA', 'Remarks'],
                'system_fields' => ['Initiator', 'SecurityCredential', 'CommandID', 'IdentifierType', 'QueueTimeOutURL', 'ResultURL'],
                'command' => 'AccountBalance',
            ],
            'b2c' => [
                'label' => 'B2C payment',
                'path' => '/mpesa/b2c/v1/paymentrequest',
                'credential_set' => 'b2c',
                'async' => true,
                'required' => ['CommandID', 'Amount', 'PartyA', 'PartyB', 'Remarks'],
                'system_fields' => ['InitiatorName', 'SecurityCredential', 'QueueTimeOutURL', 'ResultURL'],
            ],
            'stk_push' => [
                'label' => 'M-PESA Express STK Push',
                'path' => '/mpesa/stkpush/v1/processrequest',
                'credential_set' => 'c2b',
                'async' => true,
                'required' => ['BusinessShortCode', 'TransactionType', 'Amount', 'PartyA', 'PartyB', 'PhoneNumber', 'AccountReference'],
                'system_fields' => ['Password', 'Timestamp', 'CallBackURL'],
            ],
            'stk_push_query' => [
                'label' => 'STK Push query',
                'path' => '/mpesa/stkpushquery/v1/query',
                'credential_set' => 'c2b',
                'async' => false,
                'required' => ['BusinessShortCode', 'CheckoutRequestID'],
                'system_fields' => ['Password', 'Timestamp'],
            ],
            'b2b_business_buy_goods' => [
                'label' => 'B2B Business Buy Goods',
                'path' => '/mpesa/b2b/v1/paymentrequest',
                'credential_set' => 'b2b',
                'async' => true,
                'required' => ['CommandID', 'Amount', 'PartyA', 'PartyB', 'Remarks', 'AccountReference'],
                'system_fields' => ['Initiator', 'SecurityCredential', 'QueueTimeOutURL', 'ResultURL'],
            ],
            'b2b_business_paybill' => [
                'label' => 'B2B Business PayBill',
                'path' => '/mpesa/b2b/v1/paymentrequest',
                'credential_set' => 'b2b',
                'async' => true,
                'required' => ['CommandID', 'Amount', 'PartyA', 'PartyB', 'Remarks', 'AccountReference'],
                'system_fields' => ['Initiator', 'SecurityCredential', 'QueueTimeOutURL', 'ResultURL'],
            ],
            'b2b_account_topup' => [
                'label' => 'B2B account top-up',
                'path' => '/mpesa/b2b/v1/paymentrequest',
                'credential_set' => 'b2b',
                'async' => true,
                'required' => ['CommandID', 'Amount', 'PartyA', 'PartyB', 'Remarks', 'AccountReference'],
                'system_fields' => ['Initiator', 'SecurityCredential', 'QueueTimeOutURL', 'ResultURL'],
            ],
            'dynamic_qr' => [
                'label' => 'Dynamic QR Code',
                'path' => '/mpesa/qrcode/v1/generate',
                'credential_set' => 'operations',
                'async' => false,
                'required' => ['MerchantName', 'RefNo', 'Amount', 'TrxCode', 'CPI', 'Size'],
                'system_fields' => [],
            ],
            'billmanager_optin' => [
                'label' => 'Bill Manager opt-in',
                'path' => '/v1/billmanager-invoice/v1/billmanager-invoice/optin',
                'credential_set' => 'billmanager',
                'async' => false,
                'required' => ['shortcode', 'email', 'officialContact', 'sendReminders', 'callbackurl'],
                'system_fields' => [],
            ],
            'billmanager_single_invoice' => [
                'label' => 'Bill Manager single invoice',
                'path' => '/v1/billmanager-invoice/v1/billmanager-invoice/single-invoicing',
                'credential_set' => 'billmanager',
                'async' => false,
                'required' => ['externalReference', 'billedFullName', 'billedPhoneNumber', 'billedPeriod', 'invoiceName', 'dueDate', 'accountReference', 'amount'],
                'system_fields' => [],
            ],
            'billmanager_bulk_invoice' => [
                'label' => 'Bill Manager bulk invoices',
                'path' => '/v1/billmanager-invoice/v1/billmanager-invoice/bulk-invoicing',
                'credential_set' => 'billmanager',
                'async' => false,
                'required' => ['invoices'],
                'system_fields' => [],
            ],
            'billmanager_reconciliation' => [
                'label' => 'Bill Manager reconciliation',
                'path' => '/v1/billmanager-invoice/v1/billmanager-invoice/reconciliation',
                'credential_set' => 'billmanager',
                'async' => false,
                'required' => ['startDate', 'endDate'],
                'system_fields' => [],
            ],
            'billmanager_cancel_single' => [
                'label' => 'Bill Manager cancel single invoice',
                'path' => '/v1/billmanager-invoice/v1/billmanager-invoice/cancel-single-invoice',
                'credential_set' => 'billmanager',
                'async' => false,
                'required' => ['externalReference'],
                'system_fields' => [],
            ],
            'billmanager_cancel_bulk' => [
                'label' => 'Bill Manager cancel bulk invoices',
                'path' => '/v1/billmanager-invoice/v1/billmanager-invoice/cancel-bulk-invoice',
                'credential_set' => 'billmanager',
                'async' => false,
                'required' => ['externalReferences'],
                'system_fields' => [],
            ],
            'billmanager_update_onboarding' => [
                'label' => 'Bill Manager update onboarding details',
                'path' => '/v1/billmanager-invoice/v1/billmanager-invoice/change-optin-details',
                'credential_set' => 'billmanager',
                'async' => false,
                'required' => ['shortcode'],
                'system_fields' => [],
            ],
            'billmanager_update_single_invoice' => [
                'label' => 'Bill Manager update single invoice',
                'path' => '/v1/billmanager-invoice/v1/billmanager-invoice/change-invoice',
                'credential_set' => 'billmanager',
                'async' => false,
                'required' => ['externalReference'],
                'system_fields' => [],
            ],
            'billmanager_update_bulk_invoice' => [
                'label' => 'Bill Manager update bulk invoices',
                'path' => '/v1/billmanager-invoice/v1/billmanager-invoice/change-invoices',
                'credential_set' => 'billmanager',
                'async' => false,
                'required' => ['invoices'],
                'system_fields' => [],
            ],
        ];
    }

    public function submit(MpesaApiOperation $operation, array $payload): array
    {
        $definition = $this->catalog()[$operation->product] ?? throw new RuntimeException('Unknown Daraja operation.');

        try {
            $credentials = $this->credentials($definition['credential_set']);
            $payload = $this->preparePayload($operation, $definition, $payload, $credentials);

            $operation->update([
                'request_payload' => $this->redact($payload),
                'status' => 'processing',
                'submitted_at' => now(),
            ]);

            $response = Http::withToken($this->accessToken($operation->environment, $credentials))
                ->acceptJson()
                ->asJson()
                ->timeout(30)
                ->post($this->baseUrl($operation->environment).$definition['path'], $payload);

            $body = $response->json();
            if (! is_array($body)) {
                $body = ['raw_response' => $response->body()];
            }

            $responseCode = $body['ResponseCode'] ?? $body['ResultCode'] ?? null;
            $providerError = isset($body['errorCode'])
                || isset($body['errorMessage'])
                || ($responseCode !== null && (string) $responseCode !== '0');
            $success = $response->successful() && ! $providerError;
            $isAsync = $definition['async'];
            $status = ! $success ? 'failed' : ($isAsync ? 'submitted' : 'completed');
            $operation->update([
                'status' => $status,
                'response_payload' => $body,
                'provider_conversation_id' => $body['ConversationID'] ?? $body['OriginatorConversationID'] ?? null,
                'provider_transaction_id' => $body['TransactionID'] ?? null,
                'error_message' => $success
                    ? null
                    : ($body['errorMessage'] ?? $body['ResponseDescription'] ?? $body['ResultDesc'] ?? "Daraja returned HTTP {$response->status()}."),
                'completed_at' => $isAsync && $success ? null : now(),
            ]);

            if (! $success) {
                throw new RuntimeException($operation->error_message);
            }

            return $body;
        } catch (\Throwable $exception) {
            if ($operation->status !== 'failed') {
                $operation->update([
                    'status' => 'failed',
                    'error_message' => $exception->getMessage(),
                    'completed_at' => now(),
                ]);
            }

            Log::error('Daraja operation request failed', [
                'operation_id' => $operation->id,
                'product' => $operation->product,
                'environment' => $operation->environment,
                'error' => $exception->getMessage(),
            ]);
            throw $exception;
        }
    }

    public function submitPaymentReversal(LoanPayment $payment, string $reason): MpesaApiOperation
    {
        if ($payment->status !== 'posted' || $payment->payment_method !== 'mpesa') {
            throw new RuntimeException('Only posted M-PESA payments can be reversed through Daraja.');
        }

        $receipt = $payment->mpesa_receipt_number;
        if (! $receipt && $payment->mpesa_transaction_id) {
            $receipt = $payment->mpesaTransaction?->mpesa_receipt_number;
        }
        if (! $receipt) {
            throw new RuntimeException('This payment has no original M-PESA transaction ID.');
        }
        if ((int) $payment->amount_cents % 100 !== 0) {
            throw new RuntimeException('Daraja reversals require an amount in whole Kenya shillings.');
        }

        $pending = MpesaApiOperation::where('organisation_id', $payment->organisation_id)
            ->where('loan_payment_id', $payment->id)
            ->where('product', 'reversal')
            ->whereIn('status', ['queued', 'processing', 'submitted'])
            ->exists();
        if ($pending) {
            throw new RuntimeException('A reversal request for this payment is already awaiting Safaricom.');
        }

        $operation = MpesaApiOperation::create([
            'organisation_id' => $payment->organisation_id,
            'loan_payment_id' => $payment->id,
            'callback_token' => (string) Str::uuid(),
            'product' => 'reversal',
            'environment' => config('services.mpesa.env', 'sandbox'),
            'status' => 'queued',
        ]);

        $this->submit($operation, [
            'TransactionID' => $receipt,
            'Amount' => (int) ($payment->amount_cents / 100),
            'Remarks' => mb_substr($reason !== '' ? $reason : 'Payment reversal', 0, 100),
        ]);

        return $operation->fresh();
    }

    public function acceptCallback(MpesaApiOperation $operation, string $kind, array $payload): void
    {
        if (! in_array($kind, ['result', 'timeout'], true)) {
            throw new RuntimeException('Unsupported callback type.');
        }
        if (! ($this->catalog()[$operation->product]['async'] ?? false)) {
            throw new RuntimeException('This operation does not accept asynchronous callbacks.');
        }
        if (in_array($operation->status, ['completed', 'failed', 'timeout'], true)) {
            return;
        }

        $result = $payload['Result'] ?? $payload['Body']['stkCallback'] ?? $payload;
        $resultCode = $result['ResultCode'] ?? null;
        $status = $kind === 'timeout'
            ? 'timeout'
            : ($resultCode === null ? 'failed' : ((int) $resultCode === 0 ? 'completed' : 'failed'));

        $operation->update([
            'status' => $status,
            'callback_payload' => $payload,
            'provider_conversation_id' => $result['ConversationID'] ?? $operation->provider_conversation_id,
            'provider_transaction_id' => $result['TransactionID'] ?? $operation->provider_transaction_id,
            'error_message' => $status === 'failed'
                ? ($result['ResultDesc'] ?? 'Daraja returned an unsuccessful or incomplete result.')
                : null,
            'completed_at' => now(),
        ]);
    }

    private function preparePayload(
        MpesaApiOperation $operation,
        array $definition,
        array $payload,
        array $credentials,
    ): array {
        $auto = [];
        $callbackResult = $this->callbackUrl($this->operationCallbackUrl($operation, 'result'));
        $callbackTimeout = $this->callbackUrl($this->operationCallbackUrl($operation, 'timeout'));
        $orgSlug = $operation->organisation()->value('slug');
        $orgSlug = $orgSlug ?: (string) $operation->organisation_id;
        $callbackBase = $this->callbackUrl(rtrim(config('app.url'), '/')."/api/pay/{$orgSlug}");

        if (str_starts_with($operation->product, 'billmanager_')) {
            $payload = $this->replaceCallbackTokens($payload, [
                '{{result_url}}' => $callbackResult,
                '{{timeout_url}}' => $callbackTimeout,
            ]);
        }

        switch ($operation->product) {
            case 'reversal':
                $auto = [
                    'Initiator' => $credentials['initiator'],
                    'SecurityCredential' => $credentials['security_credential'],
                    'CommandID' => 'TransactionReversal',
                    'ReceiverParty' => $credentials['receiver_party'],
                    'RecieverIdentifierType' => '11',
                    'QueueTimeOutURL' => $callbackTimeout,
                    'ResultURL' => $callbackResult,
                ];
                break;
            case 'c2b_v1_register':
            case 'c2b_v2_register':
                $auto = [
                    'ConfirmationURL' => "{$callbackBase}/c2b/confirmation",
                    'ValidationURL' => "{$callbackBase}/c2b/validation",
                ];
                break;
            case 'transaction_status':
                $auto = [
                    'Initiator' => $credentials['initiator'],
                    'SecurityCredential' => $credentials['security_credential'],
                    'CommandID' => 'TransactionStatusQuery',
                    'QueueTimeOutURL' => $callbackTimeout,
                    'ResultURL' => $callbackResult,
                ];
                break;
            case 'account_balance':
                $auto = [
                    'Initiator' => $credentials['initiator'],
                    'SecurityCredential' => $credentials['security_credential'],
                    'CommandID' => 'AccountBalance',
                    'IdentifierType' => '4',
                    'QueueTimeOutURL' => $callbackTimeout,
                    'ResultURL' => $callbackResult,
                ];
                break;
            case 'b2c':
                $auto = [
                    'InitiatorName' => $credentials['initiator'],
                    'SecurityCredential' => $credentials['security_credential'],
                    'QueueTimeOutURL' => $callbackTimeout,
                    'ResultURL' => $callbackResult,
                ];
                break;
            case 'stk_push':
                $timestamp = now()->format('YmdHis');
                $shortcode = $payload['BusinessShortCode'];
                $passkey = config('services.mpesa.passkey');
                if (! $passkey) {
                    throw new RuntimeException('STK Push requires MPESA_PASSKEY.');
                }
                $auto = [
                    'Password' => base64_encode($shortcode.$passkey.$timestamp),
                    'Timestamp' => $timestamp,
                    'CallBackURL' => $callbackResult,
                ];
                break;
            case 'stk_push_query':
                $timestamp = now()->format('YmdHis');
                $shortcode = $payload['BusinessShortCode'];
                $passkey = config('services.mpesa.passkey');
                if (! $passkey) {
                    throw new RuntimeException('STK query requires MPESA_PASSKEY.');
                }
                $auto = [
                    'Password' => base64_encode($shortcode.$passkey.$timestamp),
                    'Timestamp' => $timestamp,
                ];
                break;
            case 'b2b_business_buy_goods':
            case 'b2b_business_paybill':
            case 'b2b_account_topup':
                $auto = [
                    'Initiator' => $credentials['initiator'],
                    'SecurityCredential' => $credentials['security_credential'],
                    'QueueTimeOutURL' => $callbackTimeout,
                    'ResultURL' => $callbackResult,
                ];
                break;
        }

        foreach ($auto as $key => $value) {
            if ($value === null || $value === '') {
                throw new RuntimeException("Missing required Daraja configuration for {$key}.");
            }
        }

        return array_replace($payload, $auto);
    }

    private function credentials(string $set): array
    {
        $configuration = match ($set) {
            'c2b' => [
                'consumer_key' => config('services.mpesa.consumer_key'),
                'consumer_secret' => config('services.mpesa.consumer_secret'),
            ],
            'b2c' => [
                'consumer_key' => config('services.mpesa.b2c_consumer_key'),
                'consumer_secret' => config('services.mpesa.b2c_consumer_secret'),
                'initiator' => config('services.mpesa.initiator_name'),
                'security_credential' => config('services.mpesa.security_credential'),
            ],
            'reversal' => [
                'consumer_key' => config('services.mpesa.reversal_consumer_key'),
                'consumer_secret' => config('services.mpesa.reversal_consumer_secret'),
                'initiator' => config('services.mpesa.reversal_initiator'),
                'security_credential' => config('services.mpesa.reversal_security_credential'),
                'receiver_party' => config('services.mpesa.reversal_receiver_party'),
            ],
            'b2b' => [
                'consumer_key' => config('services.mpesa.b2b_consumer_key'),
                'consumer_secret' => config('services.mpesa.b2b_consumer_secret'),
                'initiator' => config('services.mpesa.b2b_initiator'),
                'security_credential' => config('services.mpesa.b2b_security_credential'),
            ],
            'operations' => [
                'consumer_key' => config('services.mpesa.operations_consumer_key'),
                'consumer_secret' => config('services.mpesa.operations_consumer_secret'),
                'initiator' => config('services.mpesa.operations_initiator'),
                'security_credential' => config('services.mpesa.operations_security_credential'),
            ],
            'billmanager' => [
                'consumer_key' => config('services.mpesa.billmanager_consumer_key'),
                'consumer_secret' => config('services.mpesa.billmanager_consumer_secret'),
            ],
            default => throw new RuntimeException('Unknown Daraja credential set.'),
        };

        foreach (['consumer_key', 'consumer_secret'] as $field) {
            if (empty($configuration[$field])) {
                $env = match ([$set, $field]) {
                    ['c2b', 'consumer_key'] => 'MPESA_CONSUMER_KEY',
                    ['c2b', 'consumer_secret'] => 'MPESA_CONSUMER_SECRET',
                    ['b2c', 'consumer_key'] => 'MPESA_B2C_CONSUMER_KEY',
                    ['b2c', 'consumer_secret'] => 'MPESA_B2C_CONSUMER_SECRET',
                    default => 'MPESA_'.strtoupper($set).'_'.strtoupper($field),
                };
                throw new RuntimeException("Daraja {$set} credentials are missing: configure {$env}.");
            }
        }

        foreach (['initiator', 'security_credential', 'receiver_party'] as $field) {
            if (array_key_exists($field, $configuration) && empty($configuration[$field])) {
                throw new RuntimeException("Daraja {$set} configuration is missing: configure the {$field} setting.");
            }
        }

        return $configuration;
    }

    private function accessToken(string $environment, array $credentials): string
    {
        $key = 'mpesa_api_token_'.$environment.'_'.hash('sha256', $credentials['consumer_key']);

        return Cache::remember($key, now()->addMinutes(55), function () use ($environment, $credentials) {
            $response = Http::withBasicAuth($credentials['consumer_key'], $credentials['consumer_secret'])
                ->acceptJson()
                ->timeout(15)
                ->get($this->baseUrl($environment).'/oauth/v1/generate', [
                    'grant_type' => 'client_credentials',
                ]);

            if (! $response->successful()) {
                throw new RuntimeException("Daraja OAuth failed (HTTP {$response->status()}).");
            }

            $token = $response->json('access_token');
            if (! is_string($token) || $token === '') {
                throw new RuntimeException('Daraja OAuth response did not include an access token.');
            }

            return $token;
        });
    }

    private function baseUrl(string $environment): string
    {
        if (! in_array($environment, ['production', 'sandbox'], true)) {
            throw new RuntimeException('Invalid Daraja environment.');
        }

        return $environment === 'production' ? self::PRODUCTION_BASE : self::SANDBOX_BASE;
    }

    private function callbackUrl(string $url): string
    {
        if (config('services.mpesa.env', 'sandbox') !== 'production') {
            return $url;
        }

        $parts = parse_url($url);
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
            throw new RuntimeException('Production Daraja callbacks require a public HTTPS APP_URL.');
        }

        return $url;
    }

    private function operationCallbackUrl(MpesaApiOperation $operation, string $kind): string
    {
        $path = route('mpesa.operations.callback', [
            'operation' => $operation->callback_token,
            'kind' => $kind,
        ], false);

        return rtrim(config('app.url'), '/').'/'.ltrim($path, '/');
    }

    private function redact(array $payload): array
    {
        foreach (['SecurityCredential', 'Password'] as $key) {
            if (array_key_exists($key, $payload)) {
                $payload[$key] = '[REDACTED]';
            }
        }

        return $payload;
    }

    private function replaceCallbackTokens(array $payload, array $tokens): array
    {
        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $payload[$key] = $this->replaceCallbackTokens($value, $tokens);
            } elseif (is_string($value)) {
                $payload[$key] = str_replace(array_keys($tokens), array_values($tokens), $value);
            }
        }

        return $payload;
    }
}
