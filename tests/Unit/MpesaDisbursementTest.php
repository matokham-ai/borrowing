<?php

namespace Tests\Unit;

use App\Jobs\ProcessLoanDisbursement;
use App\Models\MpesaTransaction;
use App\Models\MpesaTransactionAttempt;
use App\Services\Mpesa\MpesaDisbursementService;
use Illuminate\Support\Facades\Config;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use Tests\TestCase;

class MpesaDisbursementTest extends TestCase
{
    public function test_disbursement_phone_is_normalised_to_international_format(): void
    {
        $service = (new ReflectionClass(MpesaDisbursementService::class))->newInstanceWithoutConstructor();
        $normalise = new ReflectionMethod(MpesaDisbursementService::class, 'normaliseKenyanPhone');

        $this->assertSame('254708235262', $normalise->invoke($service, '708235262'));
        $this->assertSame('254708235262', $normalise->invoke($service, '0708235262'));
        $this->assertSame('254708235262', $normalise->invoke($service, '+254 708 235 262'));
    }

    public function test_disbursement_rejects_invalid_kenyan_phone(): void
    {
        $service = (new ReflectionClass(MpesaDisbursementService::class))->newInstanceWithoutConstructor();
        $normalise = new ReflectionMethod(MpesaDisbursementService::class, 'normaliseKenyanPhone');

        $this->expectException(RuntimeException::class);
        $normalise->invoke($service, '12345');
    }

    public function test_disbursement_job_uses_the_default_queue(): void
    {
        $job = new ProcessLoanDisbursement(1, 1);

        $this->assertNull($job->queue);
    }

    public function test_b2c_token_cache_is_separated_by_environment(): void
    {
        $service = (new ReflectionClass(MpesaDisbursementService::class))->newInstanceWithoutConstructor();
        $cacheKey = new ReflectionMethod(MpesaDisbursementService::class, 'b2cTokenCacheKey');

        Config::set('services.mpesa.env', 'production');
        $productionKey = $cacheKey->invoke($service, 2);

        Config::set('services.mpesa.env', 'sandbox');
        $sandboxKey = $cacheKey->invoke($service, 2);

        $this->assertSame('mpesa_b2c_token_production_2', $productionKey);
        $this->assertSame('mpesa_b2c_token_sandbox_2', $sandboxKey);
        $this->assertNotSame($productionKey, $sandboxKey);
    }

    public function test_b2c_disbursement_requires_an_explicit_initiator_name(): void
    {
        Config::set('services.mpesa.b2c_consumer_key', 'configured');
        Config::set('services.mpesa.b2c_consumer_secret', 'configured');
        Config::set('services.mpesa.b2c_shortcode', '3014281');
        Config::set('services.mpesa.initiator_name', null);
        Config::set('services.mpesa.security_credential', 'configured');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('MPESA_INITIATOR_NAME');

        (new MpesaDisbursementService)->assertReady(1);
    }

    public function test_only_provider_auth_rejections_are_safe_to_retry(): void
    {
        $service = (new ReflectionClass(MpesaDisbursementService::class))->newInstanceWithoutConstructor();
        $isSafeAuthRetry = new ReflectionMethod(MpesaDisbursementService::class, 'isSafeAuthRetry');
        $transaction = new MpesaTransaction([
            'status' => 'failed',
            'conversation_id' => null,
        ]);
        $attempt = new MpesaTransactionAttempt([
            'status' => 'failed',
            'response_code' => '401',
        ]);

        $this->assertTrue($isSafeAuthRetry->invoke($service, $transaction, $attempt));

        $attempt->response_code = '500';
        $this->assertFalse($isSafeAuthRetry->invoke($service, $transaction, $attempt));
    }
}
