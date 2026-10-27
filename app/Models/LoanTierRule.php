<?php

namespace App\Models;

use App\Traits\MultiTenantTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class LoanTierRule extends Model
{
    use MultiTenantTrait;

    protected $fillable = [
        'organisation_id', 'loan_product_id', 'borrower_tier_id', 'maximum_amount_cents',
        'minimum_amount_cents', 'maximum_term_days', 'maximum_total_exposure_cents',
        'minimum_successful_loans', 'minimum_on_time_loans', 'minimum_consecutive_on_time_loans',
        'minimum_behaviour_score', 'minimum_financial_score', 'is_active', 'version',
    ];

    protected function casts(): array
    {
        return [
            'maximum_amount_cents' => 'integer', 'minimum_amount_cents' => 'integer', 'maximum_term_days' => 'integer',
            'maximum_total_exposure_cents' => 'integer', 'minimum_successful_loans' => 'integer',
            'minimum_on_time_loans' => 'integer', 'minimum_consecutive_on_time_loans' => 'integer',
            'minimum_behaviour_score' => 'decimal:4', 'minimum_financial_score' => 'decimal:4',
            'is_active' => 'boolean', 'version' => 'integer',
        ];
    }

    public function organisation(): BelongsTo { return $this->belongsTo(Organisation::class); }
    public function loanProduct(): BelongsTo { return $this->belongsTo(LoanProduct::class); }
    public function borrowerTier(): BelongsTo { return $this->belongsTo(BorrowerTier::class); }
    public function loanApplications(): HasMany { return $this->hasMany(LoanApplication::class); }
    public function loans(): HasMany { return $this->hasMany(Loan::class); }
    public function graduationEvents(): HasMany { return $this->hasMany(GraduationEvent::class); }
}
