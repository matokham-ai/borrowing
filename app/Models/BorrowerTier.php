<?php

namespace App\Models;

use App\Traits\MultiTenantTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class BorrowerTier extends Model
{
    use MultiTenantTrait;

    protected $fillable = [
        'organisation_id', 'tier_number', 'name', 'description', 'max_loan_amount_cents',
        'min_loan_amount_cents', 'max_term_days', 'minimum_successful_loans', 'minimum_on_time_loans',
        'minimum_consecutive_on_time_loans', 'minimum_behaviour_score', 'minimum_financial_score',
        'max_total_exposure_cents', 'is_entry_tier', 'is_active', 'version',
    ];

    protected function casts(): array
    {
        return [
            'tier_number' => 'integer', 'max_loan_amount_cents' => 'integer', 'min_loan_amount_cents' => 'integer',
            'max_term_days' => 'integer', 'minimum_successful_loans' => 'integer', 'minimum_on_time_loans' => 'integer',
            'minimum_consecutive_on_time_loans' => 'integer', 'minimum_behaviour_score' => 'decimal:4',
            'minimum_financial_score' => 'decimal:4', 'max_total_exposure_cents' => 'integer',
            'is_entry_tier' => 'boolean', 'is_active' => 'boolean', 'version' => 'integer',
        ];
    }

    public function organisation(): BelongsTo { return $this->belongsTo(Organisation::class); }
    public function borrowers(): HasMany { return $this->hasMany(Borrower::class, 'current_tier_id'); }
    public function loanTierRules(): HasMany { return $this->hasMany(LoanTierRule::class); }
    public function incomingTierHistories(): HasMany { return $this->hasMany(TierHistory::class, 'to_tier_id'); }
    public function outgoingTierHistories(): HasMany { return $this->hasMany(TierHistory::class, 'from_tier_id'); }
    public function incomingGraduationEvents(): HasMany { return $this->hasMany(GraduationEvent::class, 'to_tier_id'); }
    public function outgoingGraduationEvents(): HasMany { return $this->hasMany(GraduationEvent::class, 'from_tier_id'); }
}
