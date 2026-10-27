<?php

namespace App\Models;

use App\Traits\MultiTenantTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class GraduationEvent extends Model
{
    use MultiTenantTrait;

    protected $fillable = [
        'organisation_id', 'borrower_id', 'from_tier_id', 'to_tier_id', 'loan_id', 'event_type',
        'behaviour_score', 'financial_score', 'loan_tier_rule_id', 'tier_rule_version', 'reason',
        'decision_snapshot', 'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'behaviour_score' => 'decimal:4', 'financial_score' => 'decimal:4',
            'decision_snapshot' => 'array', 'occurred_at' => 'datetime',
        ];
    }

    public function organisation(): BelongsTo { return $this->belongsTo(Organisation::class); }
    public function borrower(): BelongsTo { return $this->belongsTo(Borrower::class); }
    public function fromTier(): BelongsTo { return $this->belongsTo(BorrowerTier::class, 'from_tier_id'); }
    public function toTier(): BelongsTo { return $this->belongsTo(BorrowerTier::class, 'to_tier_id'); }
    public function loan(): BelongsTo { return $this->belongsTo(Loan::class); }
    public function loanTierRule(): BelongsTo { return $this->belongsTo(LoanTierRule::class); }
}
