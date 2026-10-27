<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityPolicy extends Model
{
    protected $fillable = [
        'organisation_id',
        'password_min_length',
        'password_require_uppercase',
        'password_require_number',
        'password_require_symbol',
        'password_expiry_days',
        'password_history_count',
        'session_idle_minutes',
        'session_max_per_user',
        'session_single_device',
        'max_login_attempts',
        'lockout_duration_minutes',
        'two_factor_required',
        'two_factor_method',
        'ip_allowlist',
        'is_active',
    ];

    protected $casts = [
        'password_require_uppercase' => 'boolean',
        'password_require_number'    => 'boolean',
        'password_require_symbol'    => 'boolean',
        'session_single_device'      => 'boolean',
        'two_factor_required'        => 'boolean',
        'is_active'                  => 'boolean',
        'ip_allowlist'               => 'array',
        'password_min_length'        => 'integer',
        'password_expiry_days'       => 'integer',
        'password_history_count'     => 'integer',
        'session_idle_minutes'       => 'integer',
        'session_max_per_user'       => 'integer',
        'max_login_attempts'         => 'integer',
        'lockout_duration_minutes'   => 'integer',
    ];

    // ── Relationships ─────────────────────────────────────────

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    // ── Helpers ───────────────────────────────────────────────

    /**
     * Retrieve or create the policy for an organisation (singleton per org).
     */
    public static function forOrg(int $orgId): self
    {
        return self::firstOrCreate(
            ['organisation_id' => $orgId],
            [
                // Sensible defaults on first creation
                'password_min_length'        => 8,
                'password_require_uppercase' => true,
                'password_require_number'    => true,
                'password_require_symbol'    => false,
                'password_expiry_days'       => 90,
                'password_history_count'     => 5,
                'session_idle_minutes'       => 30,
                'session_max_per_user'       => 3,
                'session_single_device'      => false,
                'max_login_attempts'         => 5,
                'lockout_duration_minutes'   => 15,
                'two_factor_required'        => false,
                'two_factor_method'          => 'totp',
                'is_active'                  => true,
            ]
        );
    }

    /**
     * Render the IP allow-list as a newline-separated string for textarea display.
     */
    public function ipAllowlistText(): string
    {
        return implode("\n", $this->ip_allowlist ?? []);
    }

    /**
     * Parse a newline-separated textarea value into the JSON array.
     */
    public static function parseIpAllowlist(string $raw): array
    {
        return array_values(
            array_filter(
                array_map('trim', explode("\n", $raw))
            )
        );
    }
}
