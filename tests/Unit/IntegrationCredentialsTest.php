<?php

namespace Tests\Unit;

use App\Models\Integration;
use Tests\TestCase;

class IntegrationCredentialsTest extends TestCase
{
    public function test_credentials_are_encrypted_in_a_valid_json_column_value(): void
    {
        $credentials = [
            'consumer_key' => 'test-consumer-key',
            'consumer_secret' => 'test-consumer-secret',
        ];

        $integration = new Integration();
        $integration->credentials = $credentials;

        $stored = $integration->getAttributes()['credentials'];
        $storedEnvelope = json_decode($stored, true, flags: JSON_THROW_ON_ERROR);

        $this->assertIsArray($storedEnvelope);
        $this->assertArrayHasKey('encrypted', $storedEnvelope);
        $this->assertStringNotContainsString('test-consumer-secret', $stored);
        $this->assertSame($credentials, $integration->credentials);
    }
}
