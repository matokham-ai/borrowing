<?php

namespace App\Models;

use App\Traits\MultiTenantTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class TierHistory extends Model
{
    use MultiTenantTrait;

    protected $fillable = [
        'organisation_id', 'borrower_id', 'from_tier_id', 'to_tier_id', 'change_type', 'reason',
        'behaviour_score', 'financial_score', 'successful_loans_count', 'on_time_loans_count',
        'consecutive_on_time_loans', 'rule_version', 'decision_snapshot', 'effective_at',
    ];

    protected function casts(): array
    {
        return [
            'behaviour_score' => 'decimal:4', 'financial_score' => 'decimal:4',
            'successful_loans_count' => 'integer', 'on_time_loans_count' => 'integer',
            'consecutive_on_time_loans' => 'integer', 'decision_snapshot' => 'array', 'effective_at' => 'datetime',
        ];
    }

    public function organisation(): BelongsTo { return $this->belongsTo(Organisation::class); }
    public function borrower(): BelongsTo { return $this->belongsTo(Borrower::class); }
    public function fromTier(): BelongsTo { return $this->belongsTo(BorrowerTier::class, 'from_tier_id'); }
    public function toTier(): BelongsTo { return $this->belongsTo(BorrowerTier::class, 'to_tier_id'); }
}
