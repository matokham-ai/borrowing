<?php

namespace App\Models;

use App\Traits\MultiTenantTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class StatementDocument extends Model
{
    use MultiTenantTrait;

    protected $fillable = [
        'organisation_id', 'borrower_id', 'document_type', 'provider', 'file_key', 'original_filename',
        'mime_type', 'file_size_bytes', 'file_hash', 'statement_period_start', 'statement_period_end',
        'processing_status', 'uploaded_at', 'processed_at', 'transaction_count', 'document_metadata',
        'processing_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'file_size_bytes' => 'integer', 'statement_period_start' => 'date', 'statement_period_end' => 'date',
            'uploaded_at' => 'datetime', 'processed_at' => 'datetime', 'transaction_count' => 'integer',
            'document_metadata' => 'array', 'processing_snapshot' => 'array',
        ];
    }

    public function organisation(): BelongsTo { return $this->belongsTo(Organisation::class); }
    public function borrower(): BelongsTo { return $this->belongsTo(Borrower::class); }
    public function transactionClassifications(): HasMany { return $this->hasMany(TransactionClassification::class); }
    public function financialAnalysisRuns(): HasMany { return $this->hasMany(FinancialAnalysisRun::class); }
}
