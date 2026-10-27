<?php

namespace App\Models;

use App\Traits\MultiTenantTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Model;

class SystemEvent extends Model
{
    use MultiTenantTrait;

    protected $fillable = [
        'organisation_id', 'event_type', 'event_version', 'source', 'aggregate_type',
        'aggregate_id', 'event_id', 'idempotency_key', 'payload', 'status', 'attempts',
        'last_error', 'occurred_at', 'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'aggregate_id' => 'integer',
            'payload' => 'array',
            'attempts' => 'integer',
            'occurred_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    public function aggregate(): MorphTo
    {
        return $this->morphTo();
    }
}
