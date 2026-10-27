<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class Integration extends Model
{
    protected $fillable = [
        'organisation_id',
        'provider',
        'type',
        'label',
        'environment',
        'credentials',
        'config',
        'status',
        'last_tested_at',
        'last_error',
        'is_active',
    ];

    protected $casts = [
        'config'        => 'array',
        'is_active'     => 'boolean',
        'last_tested_at'=> 'datetime',
    ];

    // Sensitive — do NOT auto-cast to array so we can control encrypt/decrypt
    protected $hidden = ['credentials'];

    // Status constants
    const STATUS_ACTIVE   = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_ERROR    = 'error';

    // Provider constants
    const PROVIDER_ICONS = [
        'mpesa'           => 'bi-phone-vibrate-fill',
        'africas_talking' => 'bi-chat-dots-fill',
        'sendgrid'        => 'bi-envelope-fill',
        'mailgun'         => 'bi-envelope-at-fill',
        'twilio'          => 'bi-telephone-fill',
        'webhook'         => 'bi-broadcast',
        'custom'          => 'bi-plug-fill',
    ];

    const TYPES = [
        'payment' => 'Payment',
        'sms'     => 'SMS',
        'email'   => 'Email',
        'kyc'     => 'KYC',
        'webhook' => 'Webhook',
    ];

    // ── Relationships ─────────────────────────────────────────

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    // ── Scopes ────────────────────────────────────────────────

    public function scopeForOrg($query, int $orgId)
    {
        return $query->where('organisation_id', $orgId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ── Credential helpers ────────────────────────────────────

    /**
     * Store credentials as an encrypted JSON blob.
     */
    public function setCredentialsAttribute(array|string|null $value): void
    {
        if (is_null($value)) {
            $this->attributes['credentials'] = null;
            return;
        }
        $payload = is_array($value) ? json_encode($value) : $value;
        $this->attributes['credentials'] = json_encode([
            'encrypted' => Crypt::encryptString($payload),
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * Decrypt and decode credentials.
     */
    public function getCredentialsAttribute(?string $value): ?array
    {
        if (is_null($value)) {
            return null;
        }
        try {
            $stored = json_decode($value, true);
            $ciphertext = is_array($stored) && is_string($stored['encrypted'] ?? null)
                ? $stored['encrypted']
                : $value;

            return json_decode(Crypt::decryptString($ciphertext), true);
        } catch (\Exception) {
            return null;
        }
    }

    // ── Display helpers ───────────────────────────────────────

    public function icon(): string
    {
        return self::PROVIDER_ICONS[$this->provider] ?? 'bi-plug-fill';
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE   => 'badge-status--active',
            self::STATUS_ERROR    => 'badge-status--error',
            default               => 'badge-status--inactive',
        };
    }
}
