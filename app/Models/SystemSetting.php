<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemSetting extends Model
{
    protected $fillable = [
        'organisation_id',
        'group',
        'key',
        'value',
        'type',
        'label',
        'description',
        'options',
        'is_public',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'options'   => 'array',
        'is_public' => 'boolean',
        'is_active' => 'boolean',
        'sort_order'=> 'integer',
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

    public function scopeGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ── Helpers ───────────────────────────────────────────────

    /**
     * Cast the stored value to its declared PHP type.
     */
    public function typedValue(): mixed
    {
        return match ($this->type) {
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $this->value,
            'json'    => json_decode($this->value, true),
            default   => $this->value,
        };
    }
}
