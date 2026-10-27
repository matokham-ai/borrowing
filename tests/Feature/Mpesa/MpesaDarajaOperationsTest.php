<?php

namespace Tests\Feature\Mpesa;

use App\Models\LoanPayment;
use App\Models\MpesaApiOperation;
use App\Models\Organisation;
use App\Services\Mpesa\MpesaDarajaOperationsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MpesaDarajaOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_contains_the_provided_production_endpoint_paths(): void
    {
        $catalog = app(MpesaDarajaOperationsService::class)->catalog();

        $expectedPaths = [
            'reversal' => '/mpesa/reversal/v1/request',
            'c2b_v2_register' => '/mpesa/c2b/v2/registerurl',
            'c2b_v1_register' => '/mpesa/c2b/v1/registerurl',
            'transaction_status' => '/mpesa/transactionstatus/v1/query',
            'account_balance' => '/mpesa/accountbalance/v1/query',
            'b2c' => '/mpesa/b2c/v1/paymentrequest',
            'stk_push' => '/mpesa/stkpush/v1/processrequest',
            'stk_push_query' => '/mpesa/stkpushquery/v1/query',
            'b2b_business_buy_goods' => '/mpesa/b2b/v1/paymentrequest',
            'b2b_business_paybill' => '/mpesa/b2b/v1/paymentrequest',
            'b2b_account_topup' => '/mpesa/b2b/v1/paymentrequest',
            'dynamic_qr' => '/mpesa/qrcode/v1/generate',
            'billmanager_optin' => '/v1/billmanager-invoice/v1/billmanager-invoice/optin',
            'billmanager_single_invoice' => '/v1/billmanager-invoice/v1/billmanager-invoice/single-invoicing',
            'billmanager_bulk_invoice' => '/v1/billmanager-invoice/v1/billmanager-invoice/bulk-invoicing',
            'billmanager_reconciliation' => '/v1/billmanager-invoice/v1/billmanager-invoice/reconciliation',
            'billmanager_cancel_single' => '/v1/billmanager-invoice/v1/billmanager-invoice/cancel-single-invoice',
            'billmanager_cancel_bulk' => '/v1/billmanager-invoice/v1/billmanager-invoice/cancel-bulk-invoice',
            'billmanager_update_onboarding' => '/v1/billmanager-invoice/v1/billmanager-invoice/change-optin-details',
            'billmanager_update_single_invoice' => '/v1/billmanager-invoice/v1/billmanager-invoice/change-invoice',
            'billmanager_update_bulk_invoice' => '/v1/billmanager-invoice/v1/billmanager-invoice/change-invoices',
        ];

        foreach ($expectedPaths as $product => $path) {
            $this->assertSame($path, $catalog[$product]['path'], $product);
        }
    }

    public function test_reversal_request_uses_production_endpoint_and_redacts_saved_credentials(): void
    {
        Cache::flush();
        Config::set('app.url', 'https://lendflow.example');
        Config::set('services.mpesa.env', 'production');
        Config::set('services.mpesa.reversal_consumer_key', 'reversal-key');
        Config::set('services.mpesa.reversal_consumer_secret', 'reversal-secret');
        Config::set('services.mpesa.reversal_initiator', 'reversal-operator');
        Config::set('services.mpesa.reversal_security_credential', 'encrypted-credential');
        Config::set('services.mpesa.reversal_receiver_party', '3014281');

        Http::fake([
            'api.safaricom.co.ke/oauth/v1/generate*' => Http::response(['access_token' => 'access-token']),
            'api.safaricom.co.ke/mpesa/reversal/v1/request' => Http::response([
                'ResponseCode' => '0',
                'ConversationID' => 'conversation-1',
            ]),
        ]);

        $organisation = Organisation::create([
            'name' => 'Test Lender',
            'slug' => 'test-lender',
            'is_active' => true,
        ]);
        $operation = MpesaApiOperation::create([
            'organisation_id' => $organisation->id,
            'callback_token' => 'a99e88fa-e154-4f21-b874-394ef2f4d32d',
            'product' => 'reversal',
            'environment' => 'production',
            'status' => 'queued',
        ]);

        $response = app(MpesaDarajaOperationsService::class)->submit($operation, [
            'TransactionID' => 'ABC123',
            'Amount' => 100,
            'Remarks' => 'Duplicate payment',
        ]);

        $this->assertSame('0', $response['ResponseCode']);
        $this->assertSame('submitted', $operation->fresh()->status);
        $this->assertSame('[REDACTED]', $operation->fresh()->request_payload['SecurityCredential']);
        $this->assertSame('TransactionReversal', $operation->fresh()->request_payload['CommandID']);
        $this->assertSame('3014281', $operation->fresh()->request_payload['ReceiverParty']);

        Http::assertSent(fn ($request) => $request->url() === 'https://api.safaricom.co.ke/mpesa/reversal/v1/request'
            && $request['TransactionID'] === 'ABC123'
            && $request['SecurityCredential'] === 'encrypted-credential'
            && $request['RecieverIdentifierType'] === '11'
        );
    }

    public function test_reversal_fails_closed_when_product_credentials_are_not_configured(): void
    {
        Http::fake();
        Config::set('services.mpesa.reversal_consumer_key', null);
        Config::set('services.mpesa.reversal_consumer_secret', null);

        $organisation = Organisation::create([
            'name' => 'Test Lender',
            'slug' => 'test-lender',
            'is_active' => true,
        ]);
        $operation = MpesaApiOperation::create([
            'organisation_id' => $organisation->id,
            'callback_token' => '1fdbaefa-4f4d-45a7-84f4-e4fda4ad7d62',
            'product' => 'reversal',
            'environment' => 'production',
            'status' => 'queued',
        ]);

        try {
            app(MpesaDarajaOperationsService::class)->submit($operation, [
                'TransactionID' => 'ABC123',
                'Amount' => 100,
                'Remarks' => 'Duplicate payment',
            ]);
            $this->fail('A reversal request without dedicated credentials must be rejected.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('MPESA_REVERSAL_CONSUMER_KEY', $exception->getMessage());
        }

        Http::assertNothingSent();
        $this->assertSame('failed', $operation->fresh()->status);
    }

    public function test_c2b_registration_uses_registered_organisation_callback_urls(): void
    {
        Cache::flush();
        Config::set('app.url', 'https://lendflow.example');
        Config::set('services.mpesa.env', 'production');
        Config::set('services.mpesa.consumer_key', 'c2b-key');
        Config::set('services.mpesa.consumer_secret', 'c2b-secret');
        Http::fake([
            'api.safaricom.co.ke/oauth/v1/generate*' => Http::response(['access_token' => 'access-token']),
            'api.safaricom.co.ke/mpesa/c2b/v2/registerurl' => Http::response(['ResponseDescription' => 'Success']),
        ]);

        $organisation = Organisation::create([
            'name' => 'Test Lender',
            'slug' => 'test-lender',
            'is_active' => true,
        ]);
        $operation = MpesaApiOperation::create([
            'organisation_id' => $organisation->id,
            'callback_token' => 'e938b6a8-f349-4b7f-9c9d-480dbab622de',
            'product' => 'c2b_v2_register',
            'environment' => 'production',
            'status' => 'queued',
        ]);

        app(MpesaDarajaOperationsService::class)->submit($operation, [
            'ShortCode' => '3014281',
            'ResponseType' => 'Completed',
        ]);

        Http::assertSent(fn ($request) => $request->url() === 'https://api.safaricom.co.ke/mpesa/c2b/v2/registerurl'
            && $request['ConfirmationURL'] === 'https://lendflow.example/api/mpesa/test-lender/c2b/confirmation'
            && $request['ValidationURL'] === 'https://lendflow.example/api/mpesa/test-lender/c2b/validation'
        );
        $this->assertSame('completed', $operation->fresh()->status);
    }

    public function test_reversal_callback_marks_operation_complete_only_on_provider_success(): void
    {
        $organisation = Organisation::create([
            'name' => 'Test Lender',
            'slug' => 'test-lender',
            'is_active' => true,
        ]);
        $operation = MpesaApiOperation::create([
            'organisation_id' => $organisation->id,
            'callback_token' => 'fda8ca19-b090-4c16-b8ae-4f0d1972645d',
            'product' => 'reversal',
            'environment' => 'production',
            'status' => 'submitted',
        ]);

        app(MpesaDarajaOperationsService::class)->acceptCallback($operation, 'result', [
            'Result' => [
                'ResultCode' => 0,
                'ResultDesc' => 'The service request is processed successfully.',
                'TransactionID' => 'REV123',
            ],
        ]);

        $this->assertSame('completed', $operation->fresh()->status);
        $this->assertSame('REV123', $operation->fresh()->provider_transaction_id);
        $this->assertSame(0, $operation->fresh()->callback_payload['Result']['ResultCode']);
    }

    public function test_successful_payment_reversal_callback_reverses_the_local_payment(): void
    {
        $organisation = Organisation::create([
            'name' => 'Test Lender',
            'slug' => 'test-lender',
            'is_active' => true,
        ]);
        $payment = LoanPayment::create([
            'organisation_id' => $organisation->id,
            'payment_method' => 'mpesa',
            'mpesa_receipt_number' => 'PAY123',
            'amount_cents' => 10000,
            'status' => 'posted',
        ]);
        $operation = MpesaApiOperation::create([
            'organisation_id' => $organisation->id,
            'loan_payment_id' => $payment->id,
            'callback_token' => 'fbc5a337-8a8c-4d32-9f9a-34c4f34fb7d8',
            'product' => 'reversal',
            'environment' => 'production',
            'status' => 'submitted',
            'request_payload' => ['Remarks' => 'Duplicate payment'],
        ]);

        $response = $this->postJson('/api/mpesa/operations/'.$operation->callback_token.'/result', [
            'Result' => [
                'ResultCode' => 0,
                'ResultDesc' => 'Reversal successful',
                'TransactionID' => 'REV123',
            ],
        ]);

        $response->assertOk()->assertJson(['ResultCode' => 0]);
        $this->assertSame('reversed', $payment->fresh()->status);
        $this->assertSame('Duplicate payment', $payment->fresh()->reversal_reason);
        $this->assertSame('completed', $operation->fresh()->status);
    }

    public function test_failed_payment_reversal_callback_keeps_the_local_payment_posted(): void
    {
        $organisation = Organisation::create([
            'name' => 'Test Lender',
            'slug' => 'test-lender',
            'is_active' => true,
        ]);
        $payment = LoanPayment::create([
            'organisation_id' => $organisation->id,
            'payment_method' => 'mpesa',
            'mpesa_receipt_number' => 'PAY456',
            'amount_cents' => 10000,
            'status' => 'posted',
        ]);
        $operation = MpesaApiOperation::create([
            'organisation_id' => $organisation->id,
            'loan_payment_id' => $payment->id,
            'callback_token' => 'aaf3802e-4be4-46eb-8097-5c3dcaf5be4f',
            'product' => 'reversal',
            'environment' => 'production',
            'status' => 'submitted',
            'request_payload' => ['Remarks' => 'Duplicate payment'],
        ]);

        $response = $this->postJson('/api/mpesa/operations/'.$operation->callback_token.'/result', [
            'Result' => [
                'ResultCode' => 1,
                'ResultDesc' => 'Reversal rejected',
            ],
        ]);

        $response->assertOk()->assertJson(['ResultCode' => 0]);
        $this->assertSame('posted', $payment->fresh()->status);
        $this->assertSame('failed', $operation->fresh()->status);
    }
}
