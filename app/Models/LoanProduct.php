<?php

namespace App\Models;

use App\Traits\MultiTenantTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class LoanProduct extends Model
{
    use MultiTenantTrait;

    protected $fillable = [
        'organisation_id', 'code', 'name', 'description', 'minimum_amount_cents', 'maximum_amount_cents',
        'minimum_term_days', 'maximum_term_days', 'interest_type', 'interest_period', 'interest_rate', 'fee_type', 'fee_rate',
        'fixed_fee_cents', 'repayment_frequency', 'grace_period_days', 'payment_waterfall',
        'requires_appraisal', 'requires_statement', 'is_active', 'version',
    ];

    protected function casts(): array
    {
        return [
            'minimum_amount_cents' => 'integer', 'maximum_amount_cents' => 'integer', 'minimum_term_days' => 'integer',
            'maximum_term_days' => 'integer', 'interest_rate' => 'decimal:4', 'fee_rate' => 'decimal:4',
            'fixed_fee_cents' => 'integer', 'grace_period_days' => 'integer',
            'payment_waterfall' => 'array',
            'requires_appraisal' => 'boolean', 'requires_statement' => 'boolean',
            'is_active' => 'boolean', 'version' => 'integer',
        ];
    }

    public function organisation(): BelongsTo { return $this->belongsTo(Organisation::class); }
    public function loanTierRules(): HasMany { return $this->hasMany(LoanTierRule::class); }
    public function loanApplications(): HasMany { return $this->hasMany(LoanApplication::class); }
    public function loans(): HasMany { return $this->hasMany(Loan::class); }
}
