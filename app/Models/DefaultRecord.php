<?php

namespace App\Models;

use App\Traits\MultiTenantTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class DefaultRecord extends Model
{
    use MultiTenantTrait;

    protected $fillable = [
        'organisation_id', 'borrower_id', 'loan_id', 'reason', 'defaulted_at', 'days_overdue',
        'principal_outstanding_cents', 'interest_outstanding_cents', 'fees_outstanding_cents',
        'penalty_outstanding_cents', 'total_outstanding_cents', 'status', 'resolved_at', 'resolution_type',
        'amount_recovered_cents', 'amount_written_off_cents', 'default_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'defaulted_at' => 'date', 'days_overdue' => 'integer', 'principal_outstanding_cents' => 'integer',
            'interest_outstanding_cents' => 'integer', 'fees_outstanding_cents' => 'integer',
            'penalty_outstanding_cents' => 'integer', 'total_outstanding_cents' => 'integer', 'resolved_at' => 'date',
            'amount_recovered_cents' => 'integer', 'amount_written_off_cents' => 'integer', 'default_snapshot' => 'array',
        ];
    }

    public function organisation(): BelongsTo { return $this->belongsTo(Organisation::class); }
    public function borrower(): BelongsTo { return $this->belongsTo(Borrower::class); }
    public function loan(): BelongsTo { return $this->belongsTo(Loan::class); }
}
