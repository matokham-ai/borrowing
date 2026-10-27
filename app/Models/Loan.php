<?php

namespace App\Models;

use App\Traits\MultiTenantTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
      use MultiTenantTrait;

     protected static function booted(): void
     {
          static::updated(function (Loan $loan): void {
               if (!$loan->wasChanged('status') || !in_array($loan->status, ['completed', 'defaulted', 'written_off', 'cancelled'], true)) {
                    return;
               }

               $closedAt = match ($loan->status) {
                    'completed' => $loan->completed_at,
                    'defaulted' => $loan->defaulted_at,
                    'written_off' => $loan->written_off_at,
                    default => $loan->updated_at,
               };

               $loan->loanApplication()->update([
                    'security_retention_until' => ($closedAt ?? $loan->updated_at)
                         ->copy()
                         ->addYears(LoanApplication::SECURITY_DATA_RETENTION_YEARS),
               ]);
          });
     }

     protected $fillable = [
          'organisation_id', 'borrower_id', 'loan_application_id', 'loan_product_id', 'loan_tier_rule_id',
          'principal_cents', 'interest_rate', 'interest_cents', 'fees_cents', 'total_payable_cents',
          'term_days', 'repayment_frequency', 'status',
          'repayment_reference', 'disbursement_mpesa_transaction_id',
          'approved_at', 'disbursed_at', 'matures_at',
          'completed_at', 'defaulted_at', 'written_off_at',
     ];

     protected function casts(): array
     {
          return [
               'principal_cents' => 'integer', 'interest_rate' => 'decimal:6', 'interest_cents' => 'integer',
               'fees_cents' => 'integer', 'total_payable_cents' => 'integer', 'term_days' => 'integer',
               'disbursement_mpesa_transaction_id' => 'integer',
               'approved_at' => 'datetime', 'disbursed_at' => 'datetime', 'matures_at' => 'datetime',
               'completed_at' => 'datetime', 'defaulted_at' => 'datetime', 'written_off_at' => 'datetime',
          ];
     }

     public function organisation(): BelongsTo { return $this->belongsTo(Organisation::class); }
     public function borrower(): BelongsTo { return $this->belongsTo(Borrower::class); }
     public function loanApplication(): BelongsTo { return $this->belongsTo(LoanApplication::class); }
     public function loanProduct(): BelongsTo { return $this->belongsTo(LoanProduct::class); }
     public function loanTierRule(): BelongsTo { return $this->belongsTo(LoanTierRule::class); }
     public function disbursementTransaction(): BelongsTo { return $this->belongsTo(MpesaTransaction::class, 'disbursement_mpesa_transaction_id'); }
     public function schedules(): HasMany { return $this->hasMany(LoanSchedule::class); }
     public function payments(): HasMany { return $this->hasMany(LoanPayment::class); }
     public function behaviourEvents(): HasMany { return $this->hasMany(BehaviourEvent::class); }
     public function graduationEvents(): HasMany { return $this->hasMany(GraduationEvent::class); }
     public function delinquencyRecords(): HasMany { return $this->hasMany(DelinquencyRecord::class); }
     public function defaultRecord(): HasOne { return $this->hasOne(DefaultRecord::class); }
     public function mpesaTransactions(): HasMany { return $this->hasMany(MpesaTransaction::class); }
}
