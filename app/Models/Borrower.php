<?php

namespace App\Models;

use App\Traits\MultiTenantTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Borrower extends Model
{
    use MultiTenantTrait;

    protected $fillable = [
        'organisation_id',
        'branch_id',
        'registered_by',
        'user_id',
        'phone_number',
        'account_status',
        'current_tier_id',
        'lifetime_loans_count',
        'successful_loans_count',
        'on_time_loans_count',
        'consecutive_on_time_loans',
        'late_loans_count',
        'defaulted_loans_count',
        'behaviour_score',
        'unapplied_balance_cents',
        'registered_at',
        'last_loan_at',
        'last_repayment_at',
        'last_graduated_at',
    ];

    protected function casts(): array
    {
        return [
            'lifetime_loans_count' => 'integer',
            'successful_loans_count' => 'integer',
            'on_time_loans_count' => 'integer',
            'consecutive_on_time_loans' => 'integer',
            'late_loans_count' => 'integer',
            'defaulted_loans_count' => 'integer',
            'behaviour_score' => 'decimal:4',
            'unapplied_balance_cents' => 'integer',
            'registered_at' => 'datetime',
            'last_loan_at' => 'datetime',
            'last_repayment_at' => 'datetime',
            'last_graduated_at' => 'datetime',
        ];
    }

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function registrant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function currentTier(): BelongsTo
    {
        return $this->belongsTo(BorrowerTier::class, 'current_tier_id');
    }

    public function kycRecords(): HasMany
    {
        return $this->hasMany(KycRecord::class);
    }

    public function statementDocuments(): HasMany
    {
        return $this->hasMany(StatementDocument::class);
    }

    public function transactionClassifications(): HasMany
    {
        return $this->hasMany(TransactionClassification::class);
    }

    public function financialAnalysisRuns(): HasMany
    {
        return $this->hasMany(FinancialAnalysisRun::class);
    }

    public function financialProfiles(): HasMany
    {
        return $this->hasMany(FinancialProfile::class);
    }

    public function loanApplications(): HasMany
    {
        return $this->hasMany(LoanApplication::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function behaviourScores(): HasMany
    {
        return $this->hasMany(BehaviourScore::class);
    }

    public function behaviourEvents(): HasMany
    {
        return $this->hasMany(BehaviourEvent::class);
    }

    public function tierHistories(): HasMany
    {
        return $this->hasMany(TierHistory::class);
    }

    public function graduationEvents(): HasMany
    {
        return $this->hasMany(GraduationEvent::class);
    }

    public function delinquencyRecords(): HasMany
    {
        return $this->hasMany(DelinquencyRecord::class);
    }

    public function defaultRecords(): HasMany
    {
        return $this->hasMany(DefaultRecord::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function mpesaTransactions(): HasMany
    {
        return $this->hasMany(MpesaTransaction::class);
    }
}
