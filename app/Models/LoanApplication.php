<?php

namespace App\Models;

use App\Traits\MultiTenantTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class LoanApplication extends Model
{
     use MultiTenantTrait;

    public const SECURITY_DATA_RETENTION_YEARS = 7;

    protected $fillable = [
        'organisation_id', 'branch_id', 'created_by', 'first_level_approved_by', 'first_level_approved_at',
        'first_level_reason', 'final_approved_by', 'final_approved_at', 'returned_by', 'returned_at', 'return_reason',
        'borrower_id', 'loan_product_id', 'loan_tier_rule_id', 'requested_amount_cents',
        'requested_term_days', 'financial_score', 'behaviour_score', 'approved_amount_cents', 'approved_term_days',
        'status', 'decision', 'decision_reason', 'appraisal_model_version', 'tier_rule_version',
        'decision_snapshot', 'submitted_at', 'decided_at', 'expired_at',
        'security_data', 'guarantor_id_fingerprint', 'guarantor_consent_at',
        'security_requirements_required',
        'security_retention_until',
    ];

    protected function casts(): array
    {
        return [
            'requested_amount_cents' => 'integer', 'requested_term_days' => 'integer',
            'financial_score' => 'decimal:4', 'behaviour_score' => 'decimal:4',
            'approved_amount_cents' => 'integer', 'approved_term_days' => 'integer',
            'decision_snapshot' => 'array', 'submitted_at' => 'datetime', 'decided_at' => 'datetime', 'expired_at' => 'datetime',
            'security_data' => 'encrypted:array',
            'guarantor_consent_at' => 'datetime',
            'security_requirements_required' => 'boolean',
            'security_retention_until' => 'datetime',
            'first_level_approved_at' => 'datetime',
            'final_approved_at' => 'datetime',
            'returned_at' => 'datetime',
        ];
    }

    public function organisation(): BelongsTo { return $this->belongsTo(Organisation::class); }
    public function borrower(): BelongsTo { return $this->belongsTo(Borrower::class); }
    public function loanProduct(): BelongsTo { return $this->belongsTo(LoanProduct::class); }
    public function loanTierRule(): BelongsTo { return $this->belongsTo(LoanTierRule::class); }
    public function loan(): HasOne { return $this->hasOne(Loan::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function firstLevelApprover(): BelongsTo { return $this->belongsTo(User::class, 'first_level_approved_by'); }
    public function finalApprover(): BelongsTo { return $this->belongsTo(User::class, 'final_approved_by'); }

    public function hasCompleteSecurityPackage(): bool
    {
        $security = $this->security_data ?? [];
        $guarantor = $security['guarantor'] ?? [];

        if (
            empty($guarantor['name'])
            || empty($guarantor['national_id'])
            || empty($guarantor['phone'])
            || empty($guarantor['relationship'])
            || empty($guarantor['address'])
            || empty($guarantor['photo'])
            || empty($guarantor['id_front'])
            || !$this->guarantor_consent_at
        ) {
            return false;
        }

        foreach (['photo', 'id_front'] as $file) {
            if (!Storage::disk('local')->exists($guarantor[$file])) {
                return false;
            }
        }

        foreach ($security['collateral'] ?? [] as $asset) {
            if (empty($asset['photos'])) {
                return false;
            }
            foreach ($asset['photos'] as $photo) {
                if (empty($photo['path']) || !Storage::disk('local')->exists($photo['path'])) {
                    return false;
                }
            }
        }

        return true;
    }
}
