<?php

namespace App\Models;

use App\Traits\MultiTenantTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class ApiLog extends Model
{
    use MultiTenantTrait;

    protected $fillable = [
        'organisation_id', 'user_id', 'service', 'direction', 'method', 'endpoint',
        'request_reference', 'external_reference', 'http_status', 'duration_ms', 'status',
        'request_payload', 'response_payload', 'error_message', 'ip_address',
        'started_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'http_status' => 'integer',
            'duration_ms' => 'integer',
            'request_payload' => 'array',
            'response_payload' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
