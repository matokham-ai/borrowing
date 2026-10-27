<?php

namespace App\Jobs;

use App\Services\Mpesa\MpesaCallbackService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ProcessMpesaWebhook
 *
 * Thin dispatcher job that wraps an inbound Daraja HTTP callback and routes
 * it to the correct MpesaCallbackService handler.
 *
 * The webhook controller stores the raw payload and dispatches this job
 * immediately, keeping the HTTP response time well under Daraja's 5-second
 * timeout.
 *
 * Queued on the 'mpesa' queue.
 * Retried up to 3 times with exponential backoff (10 s, 60 s, 300 s).
 */
class ProcessMpesaWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    /** @var int[] Backoff delays in seconds between retry attempts. */
    public array $backoff = [10, 60, 300];

    public function __construct(
        public readonly string $callbackType,   // 'c2b_confirmation' | 'c2b_validation' | 'stk_callback' | 'b2c_result' | 'reversal_result'
        public readonly array  $payload,
        public readonly int    $organisationId,
        public readonly string $sourceIp = '',
    ) {
    }

    public function handle(MpesaCallbackService $callbackService): void
    {
        Log::info('ProcessMpesaWebhook: processing', [
            'type'            => $this->callbackType,
            'organisation_id' => $this->organisationId,
        ]);

        match ($this->callbackType) {
            'c2b_confirmation' => $callbackService->handleC2bConfirmation(
                $this->payload, $this->organisationId, $this->sourceIp
            ),
            'c2b_validation'   => $callbackService->handleC2bValidation(
                $this->payload, $this->organisationId, $this->sourceIp
            ),
            'stk_callback'     => $callbackService->handleStkCallback(
                $this->payload, $this->organisationId, $this->sourceIp
            ),
            'b2c_result'       => $callbackService->handleB2cResult(
                $this->payload, $this->organisationId, $this->sourceIp
            ),
            'reversal_result'  => $callbackService->handleReversalResult(
                $this->payload, $this->organisationId, $this->sourceIp
            ),
            default            => Log::warning('ProcessMpesaWebhook: unknown callback type', [
                'type' => $this->callbackType,
            ]),
        };
    }

    public function failed(\Throwable $e): void
    {
        Log::error('ProcessMpesaWebhook: job failed permanently', [
            'type'            => $this->callbackType,
            'organisation_id' => $this->organisationId,
            'error'           => $e->getMessage(),
        ]);
    }
}
