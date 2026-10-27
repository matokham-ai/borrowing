<?php

namespace Tests\Feature;

use App\Models\Integration;
use App\Models\Organisation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntegrationMpesaFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_mpesa_integration_uses_individual_fields_and_shows_local_callback_urls(): void
    {
        [$user, $organisation] = $this->makeAdmin();

        $this->actingAs($user)
            ->get(route('integrations.create'))
            ->assertOk()
            ->assertSee('Use ikonnect for STK')
            ->assertSee(route('mpesa.c2b.confirmation', ['orgSlug' => $organisation->slug]))
            ->assertSee(route('mpesa.c2b.validation', ['orgSlug' => $organisation->slug]))
            ->assertSee(route('mpesa.stk.callback', ['orgSlug' => $organisation->slug]))
            ->assertSee('Consumer Key');
    }

    public function test_mpesa_form_saves_individual_credentials_and_configuration(): void
    {
        [$user] = $this->makeAdmin();

        $this->actingAs($user)
            ->post(route('integrations.store'), [
                'provider' => 'mpesa',
                'type' => 'payment',
                'label' => 'M-Pesa STK',
                'environment' => 'sandbox',
                'mpesa_consumer_key' => 'consumer-key',
                'mpesa_consumer_secret' => 'consumer-secret',
                'mpesa_passkey' => 'stk-passkey',
                'shortcode' => '438789',
                'shortcode_type' => 'till',
                'use_ikonnect_for_stk' => '0',
            ])
            ->assertRedirect(route('integrations.index'));

        $integration = Integration::query()->where('provider', 'mpesa')->firstOrFail();

        $this->assertSame([
            'consumer_key' => 'consumer-key',
            'consumer_secret' => 'consumer-secret',
            'passkey' => 'stk-passkey',
        ], $integration->credentials);
        $this->assertSame('438789', $integration->config['shortcode']);
        $this->assertSame('till', $integration->config['shortcode_type']);
        $this->assertFalse($integration->config['use_ikonnect_for_stk']);
        $this->assertStringNotContainsString('consumer-secret', $integration->getRawOriginal('credentials'));
    }

    private function makeAdmin(): array
    {
        $organisation = Organisation::create([
            'name' => 'Integration Test Organisation',
            'slug' => 'integration-test-organisation',
        ]);
        $user = User::create([
            'organisation_id' => $organisation->id,
            'name' => 'Integration Admin',
            'email' => 'integration-admin@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);
        $role = Role::create([
            'organisation_id' => $organisation->id,
            'name' => 'admin',
            'display_name' => 'Administrator',
            'is_system' => true,
            'is_active' => true,
        ]);
        $user->roles()->syncWithPivotValues([$role->id], ['organisation_id' => $organisation->id]);

        return [$user, $organisation];
    }
}
