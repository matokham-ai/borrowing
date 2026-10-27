<?php

namespace App\Models;

use App\Traits\MultiTenantTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class TransactionClassification extends Model
{
    use MultiTenantTrait;

    protected $fillable = [
        'organisation_id', 'statement_document_id', 'borrower_id', 'source_transaction_id',
        'transaction_at', 'transaction_type', 'description', 'counterparty', 'amount_cents',
        'currency', 'balance_after_cents', 'classification', 'classification_confidence',
        'classifier_version', 'is_income', 'is_expense', 'is_transfer', 'is_loan_related',
        'is_repayment', 'classification_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'transaction_at' => 'datetime', 'amount_cents' => 'integer', 'balance_after_cents' => 'integer',
            'classification_confidence' => 'decimal:6', 'is_income' => 'boolean', 'is_expense' => 'boolean',
            'is_transfer' => 'boolean', 'is_loan_related' => 'boolean', 'is_repayment' => 'boolean',
            'classification_snapshot' => 'array',
        ];
    }

    public function organisation(): BelongsTo { return $this->belongsTo(Organisation::class); }
    public function statementDocument(): BelongsTo { return $this->belongsTo(StatementDocument::class); }
    public function borrower(): BelongsTo { return $this->belongsTo(Borrower::class); }
}
