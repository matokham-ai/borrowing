<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Validates production Daraja credentials by generating an OAuth access token.
 * No money moves and no callbacks fire — a safe connectivity check.
 */
class TestMpesaProduction extends Command
{
    protected $signature = 'mpesa:test-production';

    protected $description = 'Validate production Daraja credentials via OAuth (no money moves)';

    private const PRODUCTION_BASE = 'https://api.safaricom.co.ke';

    public function handle(): int
    {
        $env = config('services.mpesa.env', 'production');
        $this->info("Configured environment: {$env}");

        if ($env !== 'production') {
            $this->warn('MPESA_ENV is not "production". This command targets the production endpoint regardless, but you may want to review your config.');
        }

        $this->newLine();

        // ── Credential sets to validate ────────────────────────────────────
        $sets = [
            'C2B / STK Push' => [
                'key'    => config('services.mpesa.consumer_key'),
                'secret' => config('services.mpesa.consumer_secret'),
            ],
            'B2C disbursement' => [
                'key'    => config('services.mpesa.b2c_consumer_key'),
                'secret' => config('services.mpesa.b2c_consumer_secret'),
            ],
        ];

        // Fall back to C2B creds for any unconfigured B2C keys (the .env often
        // reuses the same app), but note it so the user knows.
        if (empty($sets['B2C disbursement']['key'])) {
            $sets['B2C disbursement'] = [
                'key'    => config('services.mpesa.consumer_key'),
                'secret' => config('services.mpesa.consumer_secret'),
            ];
            $this->line('Note: B2C consumer key not set — falling back to C2B credentials for this check.');
        }

        $overall = self::SUCCESS;

        foreach ($sets as $label => $creds) {
            $this->line("── {$label} ──────────────────────────────");

            if (empty($creds['key']) || empty($creds['secret'])) {
                $this->warn("  ✗ Credentials not configured — skipping.");
                $overall = self::FAILURE;
                continue;
            }

            $maskedKey = substr((string) $creds['key'], 0, 4) . '…' . substr((string) $creds['key'], -4);
            $this->line("  Consumer key:  {$maskedKey}");

            try {
                $response = Http::withBasicAuth($creds['key'], $creds['secret'])
                    ->acceptJson()
                    ->timeout(20)
                    ->get(self::PRODUCTION_BASE . '/oauth/v1/generate', [
                        'grant_type' => 'client_credentials',
                    ]);
            } catch (\Throwable $e) {
                $this->error("  ✗ Request failed: {$e->getMessage()}");
                $overall = self::FAILURE;
                continue;
            }

            $status = $response->status();
            $this->line("  HTTP status:    {$status}");

            if (! $response->successful()) {
                $body = $response->body();
                $this->error("  ✗ OAuth failed. Response: {$body}");
                $overall = self::FAILURE;
                continue;
            }

            $token = $response->json('access_token');
            if (! is_string($token) || $token === '') {
                $this->error('  ✗ No access_token in the response.');
                $this->line('  Body: ' . $response->body());
                $overall = self::FAILURE;
                continue;
            }

            $expiresIn = $response->json('expires_in', 'n/a');
            $tokenPreview = substr($token, 0, 8) . '…' . substr($token, -4);
            $this->info("  ✓ OAuth token obtained: {$tokenPreview}");
            $this->line("  Token expires in: {$expiresIn} seconds");
        }

        $this->newLine();
        if ($overall === self::SUCCESS) {
            $this->info('All configured credential sets authenticated successfully against production Daraja.');
        } else {
            $this->error('One or more credential sets failed to authenticate.');
        }

        $this->newLine();
        $this->comment('Reminder: This test only validates credentials (no money moved).');
        $this->comment('An STK push or B2C payment sends a real prompt / disburses real funds.');

        return $overall;
    }
}
