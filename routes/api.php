<?php

use App\Http\Controllers\Mpesa\MpesaWebhookController;
use App\Http\Controllers\Mpesa\MpesaOperationsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| These routes are stateless — no session, no CSRF.
| Applied middleware: throttle:60,1  (per-IP, 60 req/min)
|
| M-Pesa webhook endpoints are unauthenticated by design.
| Daraja cannot send bearer tokens or session cookies.
| Idempotency and origin validation are handled inside the service layer.
|
*/

/*
|--------------------------------------------------------------------------
| M-Pesa Daraja Webhooks
|--------------------------------------------------------------------------
|
| One set of endpoints per organisation, identified by {orgSlug}.
|
| Register these URLs in the Daraja portal:
|
|   C2B Confirmation URL   : https://your-domain.com/api/pay/{orgSlug}/c2b/confirmation
|   C2B Validation URL     : https://your-domain.com/api/pay/{orgSlug}/c2b/validation
|   STK Push Callback URL  : https://your-domain.com/api/pay/{orgSlug}/stk/callback
|   B2C Result URL         : https://your-domain.com/api/pay/{orgSlug}/b2c/result
|   B2C Queue Timeout URL  : https://your-domain.com/api/pay/{orgSlug}/b2c/timeout
|   Reversal Result URL    : https://your-domain.com/api/pay/{orgSlug}/reversal/result
|
*/

Route::post('/pay/operations/{operation}/{kind}', [MpesaOperationsController::class, 'callback'])
    ->whereUuid('operation')
    ->whereIn('kind', ['result', 'timeout'])
    ->name('mpesa.operations.callback');

Route::prefix('pay/{orgSlug}')
    ->name('mpesa.')
    ->group(function () {
        // C2B (inbound repayments via PayBill / Till)
        Route::post('c2b/confirmation', [MpesaWebhookController::class, 'c2bConfirmation'])
            ->name('c2b.confirmation');

        Route::post('c2b/validation', [MpesaWebhookController::class, 'c2bValidation'])
            ->name('c2b.validation');

        // STK Push callback
        Route::post('stk/callback', [MpesaWebhookController::class, 'stkCallback'])
            ->name('stk.callback');

        // B2C disbursement callbacks
        Route::post('b2c/result', [MpesaWebhookController::class, 'b2cResult'])
            ->name('b2c.result');

        Route::post('b2c/timeout', [MpesaWebhookController::class, 'b2cTimeout'])
            ->name('b2c.timeout');

        // Reversal callback
        Route::post('reversal/result', [MpesaWebhookController::class, 'reversalResult'])
            ->name('reversal.result');
    });
