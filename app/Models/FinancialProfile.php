<?php

namespace App\Models;

use App\Traits\MultiTenantTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class FinancialProfile extends Model
{
    use MultiTenantTrait;

    protected $fillable = [
        'organisation_id', 'borrower_id', 'financial_analysis_run_id', 'currency',
        'average_monthly_income_cents', 'average_monthly_expense_cents', 'average_monthly_net_cashflow_cents',
        'average_monthly_business_income_cents', 'average_monthly_salary_income_cents',
        'average_monthly_other_income_cents', 'average_monthly_debt_service_cents',
        'average_monthly_discretionary_cashflow_cents', 'income_stability_score', 'cashflow_score',
        'financial_score', 'income_volatility', 'expense_volatility', 'debt_service_ratio',
        'analysis_months', 'transaction_count', 'model_version', 'profile_snapshot', 'is_current', 'calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'average_monthly_income_cents' => 'integer', 'average_monthly_expense_cents' => 'integer',
            'average_monthly_net_cashflow_cents' => 'integer', 'average_monthly_business_income_cents' => 'integer',
            'average_monthly_salary_income_cents' => 'integer', 'average_monthly_other_income_cents' => 'integer',
            'average_monthly_debt_service_cents' => 'integer', 'average_monthly_discretionary_cashflow_cents' => 'integer',
            'income_stability_score' => 'decimal:4', 'cashflow_score' => 'decimal:4', 'financial_score' => 'decimal:4',
            'income_volatility' => 'decimal:6', 'expense_volatility' => 'decimal:6', 'debt_service_ratio' => 'decimal:6',
            'analysis_months' => 'integer', 'transaction_count' => 'integer', 'profile_snapshot' => 'array',
            'is_current' => 'boolean', 'calculated_at' => 'datetime',
        ];
    }

    public function organisation(): BelongsTo { return $this->belongsTo(Organisation::class); }
    public function borrower(): BelongsTo { return $this->belongsTo(Borrower::class); }
    public function financialAnalysisRun(): BelongsTo { return $this->belongsTo(FinancialAnalysisRun::class); }
}
