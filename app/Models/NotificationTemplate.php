<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationTemplate extends Model
{
    protected $fillable = [
        'organisation_id',
        'name',
        'event',
        'channel',
        'subject',
        'body',
        'variables',
        'is_active',
        'last_tested_at',
    ];

    protected $casts = [
        'variables'     => 'array',
        'is_active'     => 'boolean',
        'last_tested_at'=> 'datetime',
    ];

    // Channel constants
    const CHANNEL_SMS    = 'sms';
    const CHANNEL_EMAIL  = 'email';
    const CHANNEL_IN_APP = 'in_app';

    const CHANNELS = [
        self::CHANNEL_SMS    => 'SMS',
        self::CHANNEL_EMAIL  => 'Email',
        self::CHANNEL_IN_APP => 'In-App',
    ];

    // Common event constants
    const EVENTS = [
        'loan_approved'       => 'Loan Approved',
        'loan_rejected'       => 'Loan Rejected',
        'loan_disbursed'      => 'Loan Disbursed',
        'payment_received'    => 'Payment Received',
        'payment_overdue'     => 'Payment Overdue',
        'account_created'     => 'Account Created',
        'password_reset'      => 'Password Reset',
        'kyc_approved'        => 'KYC Approved',
        'kyc_rejected'        => 'KYC Rejected',
        'loan_overdue_reminder' => 'Loan Overdue Reminder',
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

    public function scopeChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ── Helpers ───────────────────────────────────────────────

    public function channelLabel(): string
    {
        return self::CHANNELS[$this->channel] ?? ucfirst($this->channel);
    }

    public function eventLabel(): string
    {
        return self::EVENTS[$this->event] ?? str_replace('_', ' ', ucwords($this->event, '_'));
    }

    /**
     * Render the body with a given set of variable substitutions.
     */
    public function render(array $vars = []): string
    {
        $body = $this->body;
        foreach ($vars as $key => $value) {
            $body = str_replace('{{' . $key . '}}', $value, $body);
        }
        return $body;
    }
}
