<?php

namespace App\Models;

use App\Traits\MultiTenantTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class MpesaTransaction extends Model
{
    use MultiTenantTrait;

    protected $fillable = [
        'organisation_id',
        'borrower_id',
        'loan_id',
        'loan_payment_id',
        'transaction_type',
        'direction',
        'status',
        'mpesa_receipt_number',
        'merchant_request_id',
        'checkout_request_id',
        'originator_conversation_id',
        'conversation_id',
        'external_reference',
        'amount_cents',
        'currency',
        'phone_number',
        'payee_name',
        'sender_name',
        'receiver_name',
        'account_reference',
        'transacted_at',
        'provider_payload',
        'result_code',
        'result_description',
        'idempotency_key',
        'received_at',
        'processed_at',
        'reconciled_at',
        'org_account_balance_cents',
    ];

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'org_account_balance_cents' => 'integer',
            'provider_payload' => 'array',
            'transacted_at' => 'datetime',
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
            'reconciled_at' => 'datetime',
        ];
    }

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    public function borrower(): BelongsTo
    {
        return $this->belongsTo(Borrower::class);
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function loanPayment(): BelongsTo
    {
        return $this->belongsTo(LoanPayment::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(MpesaTransactionAttempt::class);
    }

    public function webhookEvents(): HasMany
    {
        return $this->hasMany(MpesaWebhookEvent::class);
    }
}
