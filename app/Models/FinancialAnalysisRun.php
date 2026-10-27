<?php

namespace App\Models;

use App\Traits\MultiTenantTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class FinancialAnalysisRun extends Model
{
    use MultiTenantTrait;

    protected $fillable = [
        'organisation_id', 'borrower_id', 'statement_document_id', 'run_type', 'status',
        'model_version', 'parser_version', 'classifier_version', 'started_at', 'completed_at',
        'transaction_count', 'classified_transaction_count', 'financial_score', 'input_snapshot',
        'result_snapshot', 'error_snapshot', 'request_reference',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime', 'completed_at' => 'datetime', 'transaction_count' => 'integer',
            'classified_transaction_count' => 'integer', 'financial_score' => 'decimal:4',
            'input_snapshot' => 'array', 'result_snapshot' => 'array', 'error_snapshot' => 'array',
        ];
    }

    public function organisation(): BelongsTo { return $this->belongsTo(Organisation::class); }
    public function borrower(): BelongsTo { return $this->belongsTo(Borrower::class); }
    public function statementDocument(): BelongsTo { return $this->belongsTo(StatementDocument::class); }
    public function financialProfiles(): HasMany { return $this->hasMany(FinancialProfile::class); }
}
