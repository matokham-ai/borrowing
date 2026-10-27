<?php

namespace App\Models;

use App\Traits\MultiTenantTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class LoanPayment extends Model
{
    use MultiTenantTrait;

    protected $fillable = [
        'organisation_id', 'loan_id', 'borrower_id', 'loan_schedule_id',
        'mpesa_transaction_id', 'payment_method', 'external_reference',
        'mpesa_receipt_number', 'amount_cents', 'unapplied_cents',
        'status', 'received_at', 'posted_at', 'reversed_at', 'reversal_reason',
        'provider_payload',
    ];

    public function isUnmatched(): bool
    {
        return $this->loan_id === null;
    }

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer', 'unapplied_cents' => 'integer',
            'mpesa_transaction_id' => 'integer',
            'received_at' => 'datetime', 'posted_at' => 'datetime',
            'reversed_at' => 'datetime', 'provider_payload' => 'array',
        ];
    }

    public function organisation(): BelongsTo { return $this->belongsTo(Organisation::class); }
    public function loan(): BelongsTo { return $this->belongsTo(Loan::class); }
    public function borrower(): BelongsTo { return $this->belongsTo(Borrower::class); }
    public function loanSchedule(): BelongsTo { return $this->belongsTo(LoanSchedule::class); }
    public function mpesaTransaction(): BelongsTo { return $this->belongsTo(MpesaTransaction::class, 'mpesa_transaction_id'); }
    public function allocations(): HasMany { return $this->hasMany(PaymentAllocation::class); }
    public function behaviourEvents(): HasMany { return $this->hasMany(BehaviourEvent::class); }
    public function mpesaTransactions(): HasMany { return $this->hasMany(MpesaTransaction::class); }
}
