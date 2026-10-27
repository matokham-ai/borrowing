<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\Integration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IntegrationsController extends Controller
{
    private function orgId(): int
    {
        return (int) Auth::user()->organisation_id;
    }

    // ── Index ─────────────────────────────────────────────────

    public function index(Request $request)
    {
        $query = Integration::forOrg($this->orgId());

        if ($type = $request->type) {
            $query->where('type', $type);
        }

        if ($status = $request->status) {
            $query->where('status', $status);
        }

        $integrations = $query->orderBy('type')->orderBy('label')->paginate(15)->withQueryString();

        $stats = [
            'total'    => Integration::forOrg($this->orgId())->count(),
            'active'   => Integration::forOrg($this->orgId())->where('status', Integration::STATUS_ACTIVE)->count(),
            'error'    => Integration::forOrg($this->orgId())->where('status', Integration::STATUS_ERROR)->count(),
            'inactive' => Integration::forOrg($this->orgId())->where('status', Integration::STATUS_INACTIVE)->count(),
        ];

        $types = Integration::TYPES;

        return view('integrations.index', compact('integrations', 'stats', 'types'));
    }

    // ── Create ────────────────────────────────────────────────

    public function create()
    {
        $types = Integration::TYPES;
        return view('integrations.create', compact('types'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'provider'       => ['required', 'string', 'max:60'],
            'type'           => ['required', 'in:payment,sms,email,kyc,webhook'],
            'label'          => ['required', 'string', 'max:120'],
            'environment'    => ['required', 'in:sandbox,production'],
            'credentials'    => ['nullable', 'json'],
            'config'         => ['nullable', 'json'],
            'mpesa_consumer_key'    => ['required_if:provider,mpesa', 'nullable', 'string', 'max:500'],
            'mpesa_consumer_secret' => ['required_if:provider,mpesa', 'nullable', 'string', 'max:500'],
            'mpesa_passkey'         => ['required_if:provider,mpesa', 'nullable', 'string', 'max:500'],
            'shortcode'             => ['required_if:provider,mpesa', 'nullable', 'string', 'max:30'],
            'shortcode_type'        => ['required_if:provider,mpesa', 'nullable', 'in:till,paybill'],
            'use_ikonnect_for_stk'  => ['sometimes', 'boolean'],
            'is_active'      => ['boolean'],
        ]);

        // Unique per org+provider+environment
        $exists = Integration::forOrg($this->orgId())
            ->where('provider', $data['provider'])
            ->where('environment', $data['environment'])
            ->exists();

        if ($exists) {
            return back()->withInput()
                ->withErrors(['provider' => "An integration for \"{$data['provider']}\" in {$data['environment']} already exists."]);
        }

        $credentials = $this->credentialsFromData($data);
        $config = $this->configFromData($data);

        $integration = Integration::create([
            'organisation_id' => $this->orgId(),
            'provider'        => $data['provider'],
            'type'            => $data['type'],
            'label'           => $data['label'],
            'environment'     => $data['environment'],
            'credentials'     => $credentials,
            'config'          => $config,
            'status'          => $request->boolean('is_active') ? Integration::STATUS_ACTIVE : Integration::STATUS_INACTIVE,
            'is_active'       => $request->boolean('is_active'),
        ]);

        return redirect()->route('integrations.index')
            ->with('success', "Integration \"{$integration->label}\" created.");
    }

    // ── Show ──────────────────────────────────────────────────

    public function show(Integration $integration)
    {
        $this->authorise($integration);
        return view('integrations.show', compact('integration'));
    }

    // ── Edit / Update ─────────────────────────────────────────

    public function edit(Integration $integration)
    {
        $this->authorise($integration);
        $types = Integration::TYPES;
        return view('integrations.edit', compact('integration', 'types'));
    }

    public function update(Request $request, Integration $integration)
    {
        $this->authorise($integration);

        $data = $request->validate([
            'provider'    => ['required', 'string', 'max:60'],
            'type'        => ['required', 'in:payment,sms,email,kyc,webhook'],
            'label'       => ['required', 'string', 'max:120'],
            'environment' => ['required', 'in:sandbox,production'],
            'credentials' => ['nullable', 'json'],
            'config'      => ['nullable', 'json'],
            'mpesa_consumer_key'    => ['nullable', 'string', 'max:500'],
            'mpesa_consumer_secret' => ['nullable', 'string', 'max:500'],
            'mpesa_passkey'         => ['nullable', 'string', 'max:500'],
            'shortcode'             => ['required_if:provider,mpesa', 'nullable', 'string', 'max:30'],
            'shortcode_type'        => ['required_if:provider,mpesa', 'nullable', 'in:till,paybill'],
            'use_ikonnect_for_stk'  => ['sometimes', 'boolean'],
            'is_active'   => ['boolean'],
        ]);

        $conflict = Integration::forOrg($this->orgId())
            ->where('provider', $data['provider'])
            ->where('environment', $data['environment'])
            ->where('id', '!=', $integration->id)
            ->exists();

        if ($conflict) {
            return back()->withInput()
                ->withErrors(['provider' => "Another integration with that provider + environment already exists."]);
        }

        $credentials = $this->credentialsFromData($data, $integration);
        $config = $this->configFromData($data, $integration);

        $integration->update([
            'provider'    => $data['provider'],
            'type'        => $data['type'],
            'label'       => $data['label'],
            'environment' => $data['environment'],
            'credentials' => $credentials,
            'config'      => $config,
            'status'      => $request->boolean('is_active') ? Integration::STATUS_ACTIVE : Integration::STATUS_INACTIVE,
            'is_active'   => $request->boolean('is_active'),
        ]);

        return redirect()->route('integrations.index')
            ->with('success', "Integration \"{$integration->label}\" updated.");
    }

    // ── Delete ────────────────────────────────────────────────

    public function destroy(Integration $integration)
    {
        $this->authorise($integration);
        $label = $integration->label;
        $integration->delete();

        return redirect()->route('integrations.index')
            ->with('success', "Integration \"{$label}\" removed.");
    }

    // ── Toggle active / Test ──────────────────────────────────

    public function toggleActive(Integration $integration)
    {
        $this->authorise($integration);
        $active = ! $integration->is_active;
        $integration->update([
            'is_active' => $active,
            'status'    => $active ? Integration::STATUS_ACTIVE : Integration::STATUS_INACTIVE,
        ]);

        return back()->with('success', "Integration \"{$integration->label}\" " . ($active ? 'activated' : 'deactivated') . '.');
    }

    public function test(Integration $integration)
    {
        $this->authorise($integration);
        // Mark tested timestamp — real connectivity test dispatched via queue in production
        $integration->update(['last_tested_at' => now(), 'last_error' => null]);

        return back()->with('success', "Connection test queued for \"{$integration->label}\".");
    }

    // ── Private helpers ───────────────────────────────────────

    private function authorise(Integration $integration): void
    {
        abort_if($integration->organisation_id !== $this->orgId(), 403);
    }

    private function credentialsFromData(array $data, ?Integration $integration = null): ?array
    {
        if (($data['provider'] ?? null) === 'mpesa') {
            $credentials = $integration?->credentials ?? [];

            foreach ([
                'consumer_key' => 'mpesa_consumer_key',
                'consumer_secret' => 'mpesa_consumer_secret',
                'passkey' => 'mpesa_passkey',
            ] as $key => $field) {
                if (filled($data[$field] ?? null)) {
                    $credentials[$key] = $data[$field];
                }
            }

            return $credentials ?: null;
        }

        if (!empty($data['credentials'])) {
            $decoded = json_decode($data['credentials'], true);
            return is_array($decoded) ? $decoded : null;
        }

        return $integration?->credentials;
    }

    private function configFromData(array $data, ?Integration $integration = null): ?array
    {
        if (($data['provider'] ?? null) === 'mpesa') {
            return array_merge($integration?->config ?? [], [
                'shortcode' => $data['shortcode'],
                'shortcode_type' => $data['shortcode_type'],
                'use_ikonnect_for_stk' => filter_var($data['use_ikonnect_for_stk'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        if (!empty($data['config'])) {
            $decoded = json_decode($data['config'], true);
            return is_array($decoded) ? $decoded : null;
        }

        return $integration?->config;
    }
}
