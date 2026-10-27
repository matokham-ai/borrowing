<?php

namespace App\Models;

use App\Traits\MultiTenantTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use MultiTenantTrait;

    protected $fillable = [
        'organisation_id', 'user_id', 'action', 'auditable_type', 'auditable_id',
        'description', 'old_values', 'new_values', 'context', 'ip_address',
        'user_agent', 'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'auditable_id' => 'integer',
            'old_values' => 'array',
            'new_values' => 'array',
            'context' => 'array',
            'occurred_at' => 'datetime',
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

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}
