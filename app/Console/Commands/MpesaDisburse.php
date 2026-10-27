<?php

namespace App\Console\Commands;

use App\Models\MpesaApiOperation;
use App\Models\Organisation;
use App\Services\Mpesa\MpesaDarajaOperationsService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Send a real B2C (Business-to-Customer) M-Pesa disbursement to a phone number.
 *
 * This moves REAL money from your configured business shortcode to the
 * recipient. It records the operation in mpesa_api_operations and relies on
 * the Daraja result/timeout callbacks to mark it complete.
 *
 * Run this on the server that has outbound network access to Safaricom and
 * whose APP_URL is the public HTTPS callback target.
 */
class MpesaDisburse extends Command
{
    protected $signature = 'mpesa:disburse
                            {phone? : Recipient phone, e.g. 0708235262 or 254708235262}
                            {amount? : Whole Kenya shillings to send, e.g. 100}
                            {--org= : Organisation id or slug. Defaults to the first active org.}
                            {--command-id=BusinessPayment : B2C CommandID (BusinessPayment|SalaryPayment|PromotionPayment)}
                            {--remarks=Test disbursement : Remarks sent to Daraja}
                            {--force : Skip the interactive confirmation prompt}
                            {--list-orgs : List available organisations and exit}';

    protected $description = 'Send a real B2C M-Pesa disbursement to a phone number (moves real money)';

    private const PRODUCTION_BASE = 'https://api.safaricom.co.ke';

    public function handle(MpesaDarajaOperationsService $daraja): int
    {
        // ── List mode ──────────────────────────────────────────────────────
        if ($this->option('list-orgs')) {
            $orgs = Organisation::query()
                ->select('id', 'slug', 'name', 'is_active')
                ->orderBy('id')
                ->get();

            if ($orgs->isEmpty()) {
                $this->warn('No organisations found.');
                return self::SUCCESS;
            }

            $this->table(
                ['ID', 'Slug', 'Name', 'Active'],
                $orgs->map(fn ($o) => [$o->id, $o->slug, $o->name, $o->is_active ? 'yes' : 'no'])->all(),
            );
            return self::SUCCESS;
        }

        // ── Required arguments ────────────────────────────────────────────
        if (blank($this->argument('phone')) || blank($this->argument('amount'))) {
            $this->error('Both phone and amount are required. Usage: php artisan mpesa:disburse 0708235262 100');
            return self::FAILURE;
        }

        // ── Environment guard ───────────────────────────────────────────────
        $env = config('services.mpesa.env', 'production');
        $this->info("M-Pesa environment: {$env}");
        if ($env !== 'production') {
            $this->warn('Heads up: MPESA_ENV is not "production", but this command still uses the configured environment.');
        }

        // ── Org resolution ─────────────────────────────────────────────────
        $org = $this->resolveOrg();
        if (! $org) {
            return self::FAILURE;
        }
        $this->line("Organisation: #{$org->id} ({$org->slug}) — {$org->name}");

        // ── Phone normalisation ─────────────────────────────────────────────
        $phone = $this->normalisePhone((string) $this->argument('phone'));
        if ($phone === null) {
            $this->error('Invalid phone number. Use the format 0708235262 or 254708235262.');
            return self::FAILURE;
        }

        // ── Amount ─────────────────────────────────────────────────────────
        $amount = (int) $this->argument('amount');
        if ($amount <= 0) {
            $this->error('Amount must be a positive whole number of Kenya shillings.');
            return self::FAILURE;
        }

        $commandId = (string) $this->option('command-id');
        $validCommands = ['BusinessPayment', 'SalaryPayment', 'PromotionPayment'];
        if (! in_array($commandId, $validCommands, true)) {
            $this->error("Invalid --command-id. Choose one of: " . implode(', ', $validCommands));
            return self::FAILURE;
        }

        $remarks = (string) $this->option('remarks');
        $partyA = (string) config('services.mpesa.b2c_shortcode');
        if ($partyA === '') {
            $this->error('MPESA_B2C_SHORTCODE is not configured.');
            return self::FAILURE;
        }

        // ── Credential presence check ─────────────────────────────────────
        $missing = array_filter([
            'MPESA_B2C_CONSUMER_KEY'   => config('services.mpesa.b2c_consumer_key'),
            'MPESA_B2C_CONSUMER_SECRET' => config('services.mpesa.b2c_consumer_secret'),
            'MPESA_INITIATOR_NAME'      => config('services.mpesa.initiator_name'),
            'MPESA_SECURITY_CREDENTIAL' => config('services.mpesa.security_credential'),
        ], fn ($v) => blank($v));

        if (! empty($missing)) {
            $this->error('Missing B2C configuration: ' . implode(', ', array_keys($missing)));
            return self::FAILURE;
        }

        // ── Confirmation ───────────────────────────────────────────────────
        $this->newLine();
        $this->warn("⚠  REAL MONEY WILL MOVE.");
        $this->line("  Direction : B2C disbursement (FROM shortcode {$partyA} TO {$phone})");
        $this->line("  Amount    : KES {$amount}");
        $this->line("  CommandID : {$commandId}");
        $this->line("  Remarks   : {$remarks}");
        $this->line("  Environment: {$env}");
        $this->newLine();

        if (! $this->option('force') && ! $this->confirm('Send this disbursement now? This is irreversible.', false)) {
            $this->info('Aborted. No money was sent.');
            return self::SUCCESS;
        }

        // ── Submit via the Daraja operations service ───────────────────────
        $payload = [
            'CommandID' => $commandId,
            'Amount'    => $amount,
            'PartyA'    => $partyA,
            'PartyB'    => $phone,
            'Remarks'   => $remarks,
        ];

        $operation = MpesaApiOperation::create([
            'organisation_id' => $org->id,
            'callback_token'  => (string) Str::uuid(),
            'product'         => 'b2c',
            'environment'     => $env,
            'status'          => 'queued',
            'request_payload' => $payload,
        ]);

        try {
            $response = $daraja->submit($operation, $payload);
        } catch (\Throwable $e) {
            $this->error("Daraja request failed: {$e->getMessage()}");
            $this->line("Operation #{$operation->id} recorded as failed.");
            return self::FAILURE;
        }

        $conversationId = $response['ConversationID'] ?? $response['OriginatorConversationID'] ?? 'n/a';
        $originator = $response['OriginatorConversationID'] ?? 'n/a';

        $this->newLine();
        $this->info('✓ B2C request accepted by Daraja.');
        $this->line("  Operation ID         : #{$operation->id}");
        $this->line("  ConversationID        : {$conversationId}");
        $this->line("  OriginatorConversation: {$originator}");
        $this->line("  Status               : submitted (awaiting result callback)");
        $this->line("  Callback URL         : " . rtrim((string) config('app.url'), '/') . '/api/mpesa/operations/' . $operation->callback_token . '/result');
        $this->newLine();
        $this->comment('Daraja will call the result/timeout URL when the payment settles.');
        $this->comment("Check the outcome: php artisan tinker --execute=\"App\\Models\\MpesaApiOperation::find({$operation->id})->status;\"");

        return self::SUCCESS;
    }

    private function resolveOrg(): ?Organisation
    {
        $key = $this->option('org');

        if ($key !== null && $key !== '') {
            $org = Organisation::query()
                ->where('id', $key)
                ->orWhere('slug', $key)
                ->first();

            if (! $org) {
                $this->error("No organisation found for --org={$key}. Use --list-orgs to see options.");
                return null;
            }
            return $org;
        }

        $org = Organisation::query()->where('is_active', true)->orderBy('id')->first();

        if (! $org) {
            $this->error('No active organisation found. Pass --org=<id|slug> or use --list-orgs.');
            return null;
        }

        return $org;
    }

    private function normalisePhone(string $phone): ?string
    {
        $phone = preg_replace('/\D/', '', $phone);

        if (preg_match('/^0(7\d{8})$/', $phone)) {
            return '254' . substr($phone, 1);
        }
        if (preg_match('/^254(7\d{8})$/', $phone)) {
            return $phone;
        }

        return null;
    }
}
