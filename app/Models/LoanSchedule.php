<?php

namespace App\Models;

use App\Traits\MultiTenantTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;

class LoanSchedule extends Model
{
    use MultiTenantTrait;

    protected $fillable = [
        'organisation_id', 'loan_id', 'installment_number', 'due_date', 'principal_due_cents',
        'interest_due_cents', 'fees_due_cents', 'penalty_due_cents', 'total_due_cents',
        'principal_paid_cents', 'interest_paid_cents', 'fees_paid_cents', 'penalty_paid_cents',
        'total_paid_cents', 'status', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'installment_number' => 'integer', 'due_date' => 'date', 'principal_due_cents' => 'integer',
            'interest_due_cents' => 'integer', 'fees_due_cents' => 'integer', 'penalty_due_cents' => 'integer',
            'total_due_cents' => 'integer', 'principal_paid_cents' => 'integer', 'interest_paid_cents' => 'integer',
            'fees_paid_cents' => 'integer', 'penalty_paid_cents' => 'integer', 'total_paid_cents' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    public function organisation(): BelongsTo { return $this->belongsTo(Organisation::class); }
    public function loan(): BelongsTo { return $this->belongsTo(Loan::class); }
    public function payments(): HasMany { return $this->hasMany(LoanPayment::class); }
    public function paymentAllocations(): HasMany { return $this->hasMany(PaymentAllocation::class); }
    public function behaviourEvents(): HasMany { return $this->hasMany(BehaviourEvent::class); }
    public function delinquencyRecord(): HasOne { return $this->hasOne(DelinquencyRecord::class); }
}
