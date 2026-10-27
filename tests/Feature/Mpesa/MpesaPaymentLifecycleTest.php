<?php

namespace Tests\Feature\Mpesa;

use App\Jobs\ProcessMpesaTransaction;
use App\Models\Borrower;
use App\Models\BorrowerTier;
use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\LoanPayment;
use App\Models\LoanProduct;
use App\Models\LoanSchedule;
use App\Models\MpesaTransaction;
use App\Models\MpesaWebhookEvent;
use App\Models\Organisation;
use App\Models\PaymentAllocation;
use App\Models\User;
use App\Services\Mpesa\MpesaCallbackService;
use App\Services\Mpesa\MpesaPaymentMatcher;
use App\Services\Payments\LoanBalanceService;
use App\Services\Payments\PaymentAllocationService;
use App\Services\Payments\PaymentReversalService;
use App\Services\Payments\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * MpesaPaymentLifecycleTest
 *
 * Tests the complete inbound repayment pipeline:
 *   Webhook ingestion → matching → payment creation →
 *   allocation → balance refresh → reversal
 *
 * Uses SQLite in-memory database (phpunit.xml).
 * No real Daraja calls are made.
 */
class MpesaPaymentLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private Organisation $org;
    private Borrower     $borrower;
    private Loan         $loan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed_core_data();
    }

    // ═══════════════════════════════════════════════════════════════════════
    // 1. MpesaCallbackService — C2B ingestion
    // ═══════════════════════════════════════════════════════════════════════

    public function test_c2b_confirmation_creates_webhook_event_and_mpesa_transaction(): void
    {
        Queue::fake();

        $payload = $this->c2bPayload(
            transId:    'ABC123XYZ1',
            amount:     1000.00,
            phone:      '254712345678',
            billRef:    $this->loan->repayment_reference,
        );

        $service = app(MpesaCallbackService::class);
        $ack     = $service->handleC2bConfirmation($payload, $this->org->id, '196.201.214.200');

        // Correct ack returned
        $this->assertEquals(['ResultCode' => 0, 'ResultDesc' => 'Accepted'], $ack);

        // Webhook event persisted
        $this->assertDatabaseHas('mpesa_webhook_events', [
            'organisation_id'  => $this->org->id,
            'event_type'       => 'c2b_confirmation',
            'processing_status'=> 'queued',
        ]);

        // Mpesa transaction persisted
        $this->assertDatabaseHas('mpesa_transactions', [
            'organisation_id'      => $this->org->id,
            'transaction_type'     => 'c2b',
            'direction'            => 'inbound',
            'status'               => 'success',
            'mpesa_receipt_number' => 'ABC123XYZ1',
            'amount_cents'         => 100000,   // KES 1,000 in cents
            'phone_number'         => '254712345678',
            'account_reference'    => $this->loan->repayment_reference,
        ]);

        // Job was queued
        Queue::assertPushed(ProcessMpesaTransaction::class);
    }

    public function test_duplicate_c2b_callback_is_idempotent(): void
    {
        Queue::fake();

        $payload = $this->c2bPayload('DUP001', 500.00, '254712345678', $this->loan->repayment_reference);
        $service = app(MpesaCallbackService::class);

        // Send twice
        $service->handleC2bConfirmation($payload, $this->org->id);
        $service->handleC2bConfirmation($payload, $this->org->id);

        // Only one webhook event and one transaction created
        $this->assertEquals(1, MpesaWebhookEvent::where('organisation_id', $this->org->id)->count());
        $this->assertEquals(1, MpesaTransaction::where('mpesa_receipt_number', 'DUP001')->count());
    }

    public function test_stk_callback_with_failed_result_code_does_not_create_payment(): void
    {
        Queue::fake();

        $payload = $this->stkFailurePayload('ws_CO_test123', 1032, 'Request cancelled by user');
        $service = app(MpesaCallbackService::class);
        $ack     = $service->handleStkCallback($payload, $this->org->id);

        $this->assertEquals(['ResultCode' => 0, 'ResultDesc' => 'Accepted'], $ack);

        $this->assertDatabaseHas('mpesa_transactions', [
            'status'      => 'failed',
            'result_code' => '1032',
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // 2. MpesaPaymentMatcher
    // ═══════════════════════════════════════════════════════════════════════

    public function test_matches_by_repayment_reference(): void
    {
        $tx = $this->makeMpesaTransaction([
            'account_reference' => $this->loan->repayment_reference,
            'amount_cents'      => 50000,
            'status'            => 'success',
        ]);

        $matcher = app(MpesaPaymentMatcher::class);
        $result  = $matcher->match($tx);

        $this->assertTrue($result->matched);
        $this->assertEquals('reference', $result->match_strategy);
        $this->assertEquals('high', $result->confidence);
        $this->assertEquals($this->loan->id, $result->loan_id);
        $this->assertEquals($this->borrower->id, $result->borrower_id);
    }

    public function test_matches_by_phone_when_single_active_loan(): void
    {
        $tx = $this->makeMpesaTransaction([
            'phone_number'  => $this->borrower->phone_number,
            'amount_cents'  => 50000,
            'status'        => 'success',
        ]);

        $matcher = app(MpesaPaymentMatcher::class);
        $result  = $matcher->match($tx);

        $this->assertTrue($result->matched);
        $this->assertEquals('phone', $result->match_strategy);
        $this->assertEquals('medium', $result->confidence);
        $this->assertEquals($this->loan->id, $result->loan_id);
    }

    public function test_stk_callback_matches_legacy_checkout_id_in_conversation_id(): void
    {
        Queue::fake();
        $checkoutId = 'ws_CO_legacy_001';

        $this->makeMpesaTransaction([
            'transaction_type' => 'stk_push',
            'status' => 'processing',
            'borrower_id' => $this->borrower->id,
            'loan_id' => $this->loan->id,
            'conversation_id' => $checkoutId,
        ]);

        app(MpesaCallbackService::class)->handleStkCallback([
            'Body' => [
                'stkCallback' => [
                    'MerchantRequestID' => 'merchant-legacy-001',
                    'CheckoutRequestID' => $checkoutId,
                    'ResultCode' => 0,
                    'ResultDesc' => 'The service request is processed successfully.',
                    'CallbackMetadata' => [
                        'Item' => [
                            ['Name' => 'Amount', 'Value' => 100],
                            ['Name' => 'MpesaReceiptNumber', 'Value' => 'STKLEGACY001'],
                            ['Name' => 'TransactionDate', 'Value' => 20260101120000],
                            ['Name' => 'PhoneNumber', 'Value' => 254712345678],
                        ],
                    ],
                ],
            ],
        ], $this->org->id);

        $callbackTransaction = MpesaTransaction::where('organisation_id', $this->org->id)
            ->where('mpesa_receipt_number', 'STKLEGACY001')
            ->firstOrFail();
        $callbackTransaction->update(['status' => 'received']);

        $result = app(MpesaPaymentMatcher::class)->match($callbackTransaction);

        $this->assertTrue($result->matched);
        $this->assertSame('stk', $result->match_strategy);
        $this->assertSame($this->loan->id, $result->loan_id);
        $this->assertSame($this->borrower->id, $result->borrower_id);

        (new ProcessMpesaTransaction($callbackTransaction->id, $this->org->id))->handle(
            app(MpesaPaymentMatcher::class),
            app(PaymentService::class),
            app(PaymentAllocationService::class),
            app(LoanBalanceService::class),
            app(\App\Services\Behaviour\BehaviourScoreService::class),
            app(\App\Services\Behaviour\GraduationEngine::class),
            app(\App\Services\Mpesa\MpesaDisbursementService::class),
        );

        $this->assertDatabaseHas('loan_payments', [
            'mpesa_transaction_id' => $callbackTransaction->id,
            'loan_id' => $this->loan->id,
            'status' => 'posted',
        ]);
    }

    public function test_unmatched_when_reference_unknown(): void
    {
        $tx = $this->makeMpesaTransaction([
            'account_reference' => 'MAV-UNKNOWN',
            'amount_cents'      => 50000,
            'status'            => 'success',
        ]);

        $matcher = app(MpesaPaymentMatcher::class);
        $result  = $matcher->match($tx);

        $this->assertFalse($result->matched);
        $this->assertEquals('none', $result->confidence);
    }

    public function test_phone_match_ambiguous_with_multiple_active_loans(): void
    {
        // Create a second active loan for same borrower
        $this->createActiveLoan();

        $tx = $this->makeMpesaTransaction([
            'phone_number' => $this->borrower->phone_number,
            'amount_cents' => 50000,
            'status'       => 'success',
        ]);

        $matcher = app(MpesaPaymentMatcher::class);
        $result  = $matcher->match($tx);

        $this->assertFalse($result->matched);
        $this->assertEquals('low', $result->confidence);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // 3. PaymentService
    // ═══════════════════════════════════════════════════════════════════════

    public function test_creates_loan_payment_from_matched_transaction(): void
    {
        $tx = $this->makeMpesaTransaction([
            'borrower_id'          => $this->borrower->id,
            'loan_id'              => $this->loan->id,
            'amount_cents'         => 100000,
            'status'               => 'success',
            'mpesa_receipt_number' => 'RCP001TEST',
        ]);

        $payment = app(PaymentService::class)->createFromTransaction($tx);

        $this->assertInstanceOf(LoanPayment::class, $payment);
        $this->assertEquals('posted', $payment->status);
        $this->assertEquals(100000, $payment->amount_cents);
        $this->assertEquals('RCP001TEST', $payment->mpesa_receipt_number);
        $this->assertEquals($this->loan->id, $payment->loan_id);
        $this->assertEquals($tx->id, $payment->mpesa_transaction_id);

        // Transaction linked back
        $tx->refresh();
        $this->assertEquals($payment->id, $tx->loan_payment_id);
    }

    public function test_unmatched_transaction_is_saved_for_reconciliation_without_a_loan(): void
    {
        $tx = $this->makeMpesaTransaction([
            'mpesa_receipt_number' => 'UNMATCHED001',
            'amount_cents' => 10000,
            'status' => 'received',
        ]);

        $payment = app(PaymentService::class)->createUnmatchedFromTransaction($tx);

        $this->assertNull($payment->loan_id);
        $this->assertNull($payment->borrower_id);
        $this->assertSame('pending', $payment->status);
        $this->assertSame(10000, $payment->unapplied_cents);
        $this->assertSame($payment->id, $tx->fresh()->loan_payment_id);
    }

    public function test_payment_creation_is_idempotent_on_receipt_number(): void
    {
        $tx = $this->makeMpesaTransaction([
            'borrower_id'          => $this->borrower->id,
            'loan_id'              => $this->loan->id,
            'amount_cents'         => 100000,
            'status'               => 'success',
            'mpesa_receipt_number' => 'IDEM001',
        ]);

        $svc = app(PaymentService::class);
        $p1  = $svc->createFromTransaction($tx);
        $p2  = $svc->createFromTransaction($tx);

        $this->assertEquals($p1->id, $p2->id);
        $this->assertEquals(1, LoanPayment::where('mpesa_receipt_number', 'IDEM001')->count());
    }

    public function test_throws_when_transaction_not_success(): void
    {
        $this->expectException(\RuntimeException::class);

        $tx = $this->makeMpesaTransaction([
            'borrower_id' => $this->borrower->id,
            'loan_id'     => $this->loan->id,
            'amount_cents'=> 100000,
            'status'      => 'failed',
        ]);

        app(PaymentService::class)->createFromTransaction($tx);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // 4. PaymentAllocationService — waterfall
    // ═══════════════════════════════════════════════════════════════════════

    public function test_full_payment_allocates_across_waterfall(): void
    {
        // Schedule: 1 instalment — principal 5000, interest 500, fees 100 cents
        $schedule = $this->loan->schedules()->first();
        $schedule->update([
            'principal_due_cents' => 5000,
            'interest_due_cents'  => 500,
            'fees_due_cents'      => 100,
            'total_due_cents'     => 5600,
        ]);

        $payment = $this->makePostedPayment(5600);

        $allocations = app(PaymentAllocationService::class)->allocate($payment);

        $this->assertNotEmpty($allocations);

        $byType = collect($allocations)->keyBy('allocation_type');

        // Default waterfall: penalty(0) → fees → interest → principal
        $this->assertEquals(100,  $byType->get('fees')->amount_cents);
        $this->assertEquals(500,  $byType->get('interest')->amount_cents);
        $this->assertEquals(5000, $byType->get('principal')->amount_cents);

        // Total allocated equals payment
        $totalAllocated = collect($allocations)->sum('amount_cents');
        $this->assertEquals(5600, $totalAllocated);
    }

    public function test_partial_payment_leaves_schedule_outstanding(): void
    {
        $schedule = $this->loan->schedules()->first();
        $schedule->update([
            'principal_due_cents' => 5000,
            'interest_due_cents'  => 500,
            'fees_due_cents'      => 0,
            'total_due_cents'     => 5500,
        ]);

        // Pay only 2000 out of 5500
        $payment = $this->makePostedPayment(2000);

        app(PaymentAllocationService::class)->allocate($payment);

        $totalAllocated = PaymentAllocation::where('loan_payment_id', $payment->id)
            ->sum('amount_cents');

        $this->assertEquals(2000, $totalAllocated);

        // Payment's unapplied_cents should be 0 — not an overpayment
        $payment->refresh();
        $this->assertEquals(0, $payment->unapplied_cents);
    }

    public function test_overpayment_stored_in_unapplied_cents(): void
    {
        $schedule = $this->loan->schedules()->first();
        $schedule->update([
            'principal_due_cents' => 3000,
            'interest_due_cents'  => 300,
            'fees_due_cents'      => 0,
            'total_due_cents'     => 3300,
        ]);

        // Pay 4000 — 700 is surplus
        $payment = $this->makePostedPayment(4000);

        app(PaymentAllocationService::class)->allocate($payment);

        $payment->refresh();
        $this->assertEquals(700, $payment->unapplied_cents);

        // Borrower credit balance incremented
        $this->borrower->refresh();
        $this->assertEquals(700, $this->borrower->unapplied_balance_cents);
    }

    public function test_allocation_is_idempotent(): void
    {
        $schedule = $this->loan->schedules()->first();
        $schedule->update(['principal_due_cents' => 2000, 'total_due_cents' => 2000]);

        $payment = $this->makePostedPayment(2000);

        $svc = app(PaymentAllocationService::class);
        $svc->allocate($payment);
        $svc->allocate($payment); // second call — should be a no-op

        $this->assertEquals(1, PaymentAllocation::where('loan_payment_id', $payment->id)->count());
    }

    public function test_product_waterfall_override_is_respected(): void
    {
        // Override: fees last instead of penalty → fees → interest → principal
        $this->loan->loanProduct->update([
            'payment_waterfall' => ['interest', 'principal', 'fees'],
        ]);

        $schedule = $this->loan->schedules()->first();
        $schedule->update([
            'interest_due_cents'  => 500,
            'principal_due_cents' => 5000,
            'fees_due_cents'      => 200,
            'total_due_cents'     => 5700,
        ]);

        // Pay only 600 — should satisfy interest (500) then start on principal (100)
        $payment     = $this->makePostedPayment(600);
        $allocations = app(PaymentAllocationService::class)->allocate($payment);

        $byType = collect($allocations)->keyBy('allocation_type');

        $this->assertEquals(500, $byType->get('interest')->amount_cents);
        $this->assertEquals(100, $byType->get('principal')->amount_cents);
        $this->assertNull($byType->get('fees')); // not reached
    }

    // ═══════════════════════════════════════════════════════════════════════
    // 5. LoanBalanceService — schedule + loan status
    // ═══════════════════════════════════════════════════════════════════════

    public function test_fully_paid_schedule_status_becomes_paid(): void
    {
        $schedule = $this->loan->schedules()->first();
        $schedule->update(['principal_due_cents' => 2000, 'total_due_cents' => 2000]);

        $payment = $this->makePostedPayment(2000);
        app(PaymentAllocationService::class)->allocate($payment);
        app(LoanBalanceService::class)->refreshAfterPayment($payment);

        $schedule->refresh();
        $this->assertEquals('paid', $schedule->status);
        $this->assertNotNull($schedule->paid_at);
    }

    public function test_loan_status_becomes_completed_when_all_schedules_paid(): void
    {
        // Single-instalment loan — pay in full
        $schedule = $this->loan->schedules()->first();
        $schedule->update([
            'principal_due_cents' => 5000,
            'interest_due_cents'  => 500,
            'fees_due_cents'      => 100,
            'total_due_cents'     => 5600,
        ]);

        $payment = $this->makePostedPayment(5600);
        app(PaymentAllocationService::class)->allocate($payment);
        app(LoanBalanceService::class)->refreshAfterPayment($payment);

        $this->loan->refresh();
        $this->assertEquals('completed', $this->loan->status);
        $this->assertNotNull($this->loan->completed_at);
    }

    public function test_partial_payment_leaves_loan_active(): void
    {
        $schedule = $this->loan->schedules()->first();
        $schedule->update(['principal_due_cents' => 5000, 'total_due_cents' => 5000]);

        $payment = $this->makePostedPayment(1000);
        app(PaymentAllocationService::class)->allocate($payment);
        app(LoanBalanceService::class)->refreshAfterPayment($payment);

        $this->loan->refresh();
        $this->assertEquals('active', $this->loan->status);
    }

    public function test_last_repayment_at_stamped_on_borrower(): void
    {
        $schedule = $this->loan->schedules()->first();
        $schedule->update(['principal_due_cents' => 1000, 'total_due_cents' => 1000]);

        $payment = $this->makePostedPayment(1000);
        app(PaymentAllocationService::class)->allocate($payment);
        app(LoanBalanceService::class)->refreshAfterPayment($payment);

        $this->borrower->refresh();
        $this->assertNotNull($this->borrower->last_repayment_at);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // 6. PaymentReversalService
    // ═══════════════════════════════════════════════════════════════════════

    public function test_reversal_stamps_payment_reversed(): void
    {
        $schedule = $this->loan->schedules()->first();
        $schedule->update(['principal_due_cents' => 3000, 'total_due_cents' => 3000]);

        $payment = $this->makePostedPayment(3000, 'REV001');
        app(PaymentAllocationService::class)->allocate($payment);
        app(LoanBalanceService::class)->refreshAfterPayment($payment);

        app(PaymentReversalService::class)->reverseByReceipt('REV001', $this->org->id, 'Test reversal');

        $payment->refresh();
        $this->assertEquals('reversed', $payment->status);
        $this->assertNotNull($payment->reversed_at);
        $this->assertEquals('Test reversal', $payment->reversal_reason);
    }

    public function test_reversal_restores_schedule_outstanding(): void
    {
        $schedule = $this->loan->schedules()->first();
        $schedule->update(['principal_due_cents' => 3000, 'total_due_cents' => 3000]);

        $payment = $this->makePostedPayment(3000, 'REV002');
        $svc     = app(PaymentAllocationService::class);
        $bal     = app(LoanBalanceService::class);
        $rev     = app(PaymentReversalService::class);

        $svc->allocate($payment);
        $bal->refreshAfterPayment($payment);

        // After payment + allocation: schedule should be paid
        $schedule->refresh();
        $this->assertEquals('paid', $schedule->status);

        // Reverse it
        $rev->reverseByReceipt('REV002', $this->org->id);
        $bal->refreshAfterPayment($payment); // re-aggregates without reversed payment

        // Schedule goes back to pending (no posted allocations remain)
        $schedule->refresh();
        $this->assertNotEquals('paid', $schedule->status);
    }

    public function test_reversal_reclaims_unapplied_balance(): void
    {
        $schedule = $this->loan->schedules()->first();
        $schedule->update(['principal_due_cents' => 1000, 'total_due_cents' => 1000]);

        // Overpayment: 700 unapplied
        $payment = $this->makePostedPayment(1700, 'REV003');
        app(PaymentAllocationService::class)->allocate($payment);
        app(LoanBalanceService::class)->refreshAfterPayment($payment);

        // Verify the payment record has unapplied_cents populated (bypass Multi Tenant scope)
        $paymentUnapplied = \DB::table('loan_payments')->where('id', $payment->id)->value('unapplied_cents');
        $this->assertEquals(700, $paymentUnapplied, 'Payment should have 700 unapplied cents after allocation');

        $borrowerUnappliedBefore = \DB::table('borrowers')->where('id', $this->borrower->id)->value('unapplied_balance_cents');
        $this->assertEquals(700, $borrowerUnappliedBefore, 'Borrower should have 700 unapplied balance after overpayment');

        app(PaymentReversalService::class)->reverseByReceipt('REV003', $this->org->id);

        // Query directly via DB::table() to bypass MultiTenantTrait global scope
        $unappliedAfter = \DB::table('borrowers')
            ->where('id', $this->borrower->id)
            ->value('unapplied_balance_cents');
        
        $this->assertEquals(0, $unappliedAfter, 
            'Reversal should have decremented unapplied_balance_cents from 700 to 0');
    }

    public function test_double_reversal_is_idempotent(): void
    {
        $schedule = $this->loan->schedules()->first();
        $schedule->update(['principal_due_cents' => 500, 'total_due_cents' => 500]);

        $payment = $this->makePostedPayment(500, 'REV004');
        app(PaymentAllocationService::class)->allocate($payment);

        $rev = app(PaymentReversalService::class);
        $rev->reverseByReceipt('REV004', $this->org->id);
        $rev->reverseByReceipt('REV004', $this->org->id); // second call — no exception

        $this->assertEquals(1, LoanPayment::where('mpesa_receipt_number', 'REV004')
            ->where('status', 'reversed')->count());
    }

    // ═══════════════════════════════════════════════════════════════════════
    // 7. Webhook endpoint (HTTP)
    // ═══════════════════════════════════════════════════════════════════════

    public function test_c2b_webhook_endpoint_returns_200_and_queues_job(): void
    {
        Queue::fake();

        $payload = $this->c2bPayload('WEB001', 1000.00, '254712345678', $this->loan->repayment_reference);

        $response = $this->postJson(
            "/api/pay/{$this->org->slug}/c2b/confirmation",
            $payload
        );

        $response->assertStatus(200)
                 ->assertJson(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);

        // The webhook controller dispatches ProcessMpesaWebhook (not ProcessMpesaTransaction).
        // ProcessMpesaTransaction is dispatched later by MpesaCallbackService inside the job.
        Queue::assertPushed(\App\Jobs\ProcessMpesaWebhook::class, function ($job) {
            return $job->callbackType === 'c2b_confirmation';
        });
    }

    public function test_webhook_returns_200_for_unknown_org_slug(): void
    {
        Queue::fake();

        $response = $this->postJson(
            '/api/pay/unknown-org-xyz/c2b/confirmation',
            $this->c2bPayload('X001', 100, '254700000000', 'MAV-UNKNOWN')
        );

        // Returns 200 to prevent Daraja retry loops
        $response->assertStatus(200)
                 ->assertJson(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);

        Queue::assertNotPushed(ProcessMpesaTransaction::class);
    }

    public function test_all_six_webhook_routes_exist_and_accept_post(): void
    {
        Queue::fake();

        $routes = [
            "/api/pay/{$this->org->slug}/c2b/confirmation",
            "/api/pay/{$this->org->slug}/c2b/validation",
            "/api/pay/{$this->org->slug}/stk/callback",
            "/api/pay/{$this->org->slug}/b2c/result",
            "/api/pay/{$this->org->slug}/b2c/timeout",
            "/api/pay/{$this->org->slug}/reversal/result",
        ];

        foreach ($routes as $route) {
            $this->postJson($route, ['dummy' => true])
                 ->assertStatus(200)
                 ->assertJson(['ResultCode' => 0]);
        }
    }

    public function test_b2c_result_callback_records_success_without_a_queue_worker(): void
    {
        $this->loan->update(['status' => 'disbursement_pending']);
        $transaction = $this->makeMpesaTransaction([
            'transaction_type' => 'b2c',
            'direction' => 'outbound',
            'status' => 'submitted',
            'loan_id' => $this->loan->id,
            'borrower_id' => $this->borrower->id,
            'amount_cents' => 50000,
            'originator_conversation_id' => 'originator-b2c-123',
            'conversation_id' => 'conversation-b2c-123',
        ]);

        $response = $this->postJson("/api/pay/{$this->org->slug}/b2c/result", [
            'Result' => [
                'ResultCode' => 0,
                'ResultDesc' => 'The service request is processed successfully.',
                'OriginatorConversationID' => 'originator-b2c-123',
                'ConversationID' => 'conversation-b2c-123',
                'TransactionID' => 'B2CRECEIPT1',
                'ResultParameters' => [
                    'ResultParameter' => [
                        ['Key' => 'TransactionAmount', 'Value' => 500],
                        ['Key' => 'TransactionCompletedDateTime', 'Value' => now()->format('d.m.Y H:i:s')],
                    ],
                ],
            ],
        ]);

        $response->assertOk()->assertJson(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
        $this->assertSame('success', $transaction->fresh()->status);
        $this->assertSame('B2CRECEIPT1', $transaction->fresh()->mpesa_receipt_number);
        $this->assertNotNull($transaction->fresh()->processed_at);
        $this->assertSame('active', $this->loan->fresh()->status);
        $this->assertDatabaseHas('mpesa_webhook_events', [
            'organisation_id' => $this->org->id,
            'event_type' => 'b2c_result',
            'processing_status' => 'processed',
        ]);
    }

    public function test_loan_disbursement_uses_registered_b2c_callback_routes(): void
    {
        Cache::flush();
        Config::set('app.url', 'https://lendflow.example');
        Config::set('services.mpesa.env', 'production');
        Config::set('services.mpesa.b2c_consumer_key', 'b2c-key');
        Config::set('services.mpesa.b2c_consumer_secret', 'b2c-secret');
        Config::set('services.mpesa.b2c_shortcode', '3014281');
        Config::set('services.mpesa.initiator_name', 'test-initiator');
        Config::set('services.mpesa.security_credential', 'test-credential');
        Http::fake([
            'api.safaricom.co.ke/oauth/v1/generate*' => Http::response(['access_token' => 'access-token']),
            'api.safaricom.co.ke/mpesa/b2c/v1/paymentrequest' => Http::response([
                'ResponseCode' => '0',
                'ConversationID' => 'conversation-1',
            ]),
        ]);
        $this->loan->update(['status' => 'disbursement_pending']);

        app(\App\Services\Mpesa\MpesaDisbursementService::class)->disburse($this->loan->fresh());

        Http::assertSent(fn ($request) => $request->url() === 'https://api.safaricom.co.ke/mpesa/b2c/v1/paymentrequest'
            && $request['ResultURL'] === "https://lendflow.example/api/pay/{$this->org->slug}/b2c/result"
            && $request['QueueTimeOutURL'] === "https://lendflow.example/api/pay/{$this->org->slug}/b2c/timeout"
        );
        $this->assertSame('disbursement_pending', $this->loan->fresh()->status);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // Helpers
    // ═══════════════════════════════════════════════════════════════════════

    private function seed_core_data(): void
    {
        $this->org = Organisation::create([
            'name'     => 'Test Lender',
            'slug'     => 'test-lender',
            'is_active'=> true,
        ]);

        $tier = BorrowerTier::create([
            'organisation_id'  => $this->org->id,
            'tier_number'      => 1,
            'name'             => 'Bronze',
            'max_loan_amount_cents' => 1_000_000,
            'max_term_days'    => 30,
            'is_entry_tier'    => true,
            'is_active'        => true,
        ]);

        $user = User::create([
            'organisation_id' => $this->org->id,
            'name'            => 'Test Borrower',
            'email'           => 'borrower@test.com',
            'password'        => bcrypt('password'),
        ]);

        $this->borrower = Borrower::create([
            'organisation_id'  => $this->org->id,
            'user_id'          => $user->id,
            'phone_number'     => '254712345678',
            'account_status'   => 'active',
            'current_tier_id'  => $tier->id,
        ]);

        $product = LoanProduct::create([
            'organisation_id'      => $this->org->id,
            'code'                 => 'MICRO1',
            'name'                 => 'Micro Loan',
            'minimum_amount_cents' => 10000,
            'maximum_amount_cents' => 500000,
            'minimum_term_days'    => 7,
            'maximum_term_days'    => 30,
            'interest_type'        => 'flat',
            'interest_rate'        => 0.10,
            'fee_type'             => 'none',
            'fee_rate'             => 0,
            'repayment_frequency'  => 'monthly',
            'is_active'            => true,
        ]);

        $application = LoanApplication::create([
            'organisation_id'        => $this->org->id,
            'borrower_id'            => $this->borrower->id,
            'loan_product_id'        => $product->id,
            'requested_amount_cents' => 50000,
            'requested_term_days'    => 30,
            'approved_amount_cents'  => 50000,
            'approved_term_days'     => 30,
            'status'                 => 'loan_created',
            'decision'               => 'approved',
            'submitted_at'           => now(),
            'decided_at'             => now(),
        ]);

        $this->loan = Loan::create([
            'organisation_id'      => $this->org->id,
            'borrower_id'          => $this->borrower->id,
            'loan_application_id'  => $application->id,
            'loan_product_id'      => $product->id,
            'principal_cents'      => 50000,
            'interest_rate'        => 0.10,
            'interest_cents'       => 5000,
            'fees_cents'           => 0,
            'total_payable_cents'  => 55000,
            'term_days'            => 30,
            'repayment_frequency'  => 'monthly',
            'repayment_reference'  => 'MAV-00000001',
            'status'               => 'active',
            'approved_at'          => now(),
            'disbursed_at'         => now(),
            'matures_at'           => now()->addDays(30),
        ]);

        // One schedule instalment
        LoanSchedule::create([
            'organisation_id'      => $this->org->id,
            'loan_id'              => $this->loan->id,
            'installment_number'   => 1,
            'due_date'             => now()->addDays(30)->toDateString(),
            'principal_due_cents'  => 50000,
            'interest_due_cents'   => 5000,
            'fees_due_cents'       => 0,
            'penalty_due_cents'    => 0,
            'total_due_cents'      => 55000,
            'status'               => 'pending',
        ]);
    }

    private function c2bPayload(
        string $transId,
        float  $amount,
        string $phone,
        string $billRef,
    ): array {
        return [
            'TransactionType'    => 'Pay Bill',
            'TransID'            => $transId,
            'TransTime'          => now()->format('YmdHis'),
            'TransAmount'        => (string) $amount,
            'BusinessShortCode'  => '3014281',
            'BillRefNumber'      => $billRef,
            'InvoiceNumber'      => '',
            'OrgAccountBalance'  => '100000.00',
            'ThirdPartyTransID'  => '',
            'MSISDN'             => $phone,
            'FirstName'          => 'Test',
            'MiddleName'         => '',
            'LastName'           => 'User',
        ];
    }

    private function stkFailurePayload(string $checkoutId, int $resultCode, string $desc): array
    {
        return [
            'Body' => [
                'stkCallback' => [
                    'MerchantRequestID'  => 'merch-' . $checkoutId,
                    'CheckoutRequestID'  => $checkoutId,
                    'ResultCode'         => $resultCode,
                    'ResultDesc'         => $desc,
                ],
            ],
        ];
    }

    private function makeMpesaTransaction(array $attrs): MpesaTransaction
    {
        return MpesaTransaction::create(array_merge([
            'organisation_id'  => $this->org->id,
            'transaction_type' => 'c2b',
            'direction'        => 'inbound',
            'status'           => 'success',
            'idempotency_key'  => 'test-' . uniqid(),
            'amount_cents'     => 50000,
            'received_at'      => now(),
        ], $attrs));
    }

    private function makePostedPayment(int $amountCents, string $receipt = ''): LoanPayment
    {
        return LoanPayment::create([
            'organisation_id'      => $this->org->id,
            'loan_id'              => $this->loan->id,
            'borrower_id'          => $this->borrower->id,
            'payment_method'       => 'mpesa',
            'mpesa_receipt_number' => $receipt ?: ('TEST-' . uniqid()),
            'amount_cents'         => $amountCents,
            'unapplied_cents'      => 0,
            'status'               => 'posted',
            'received_at'          => now(),
            'posted_at'            => now(),
        ]);
    }

    /** Create a second active loan for the same borrower (for ambiguity tests). */
    private function createActiveLoan(): Loan
    {
        $app = LoanApplication::create([
            'organisation_id'        => $this->org->id,
            'borrower_id'            => $this->borrower->id,
            'loan_product_id'        => $this->loan->loan_product_id,
            'requested_amount_cents' => 20000,
            'requested_term_days'    => 30,
            'approved_amount_cents'  => 20000,
            'approved_term_days'     => 30,
            'status'                 => 'loan_created',
            'decision'               => 'approved',
            'submitted_at'           => now(),
            'decided_at'             => now(),
        ]);

        return Loan::create([
            'organisation_id'      => $this->org->id,
            'borrower_id'          => $this->borrower->id,
            'loan_application_id'  => $app->id,
            'loan_product_id'      => $this->loan->loan_product_id,
            'principal_cents'      => 20000,
            'interest_rate'        => 0.10,
            'interest_cents'       => 2000,
            'fees_cents'           => 0,
            'total_payable_cents'  => 22000,
            'term_days'            => 30,
            'repayment_frequency'  => 'monthly',
            'repayment_reference'  => 'MAV-00000002',
            'status'               => 'active',   // must be 'active' for matcher to find it
            'approved_at'          => now(),
            'disbursed_at'         => now(),
            'matures_at'           => now()->addDays(30),
        ]);
    }
}
