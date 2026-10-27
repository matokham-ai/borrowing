<?php

namespace App\Models;

use App\Traits\MultiTenantTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class DelinquencyRecord extends Model
{
    use MultiTenantTrait;

    protected $fillable = [
        'organisation_id', 'borrower_id', 'loan_id', 'loan_schedule_id', 'due_date', 'delinquent_from',
        'cleared_at', 'days_overdue', 'amount_due_cents', 'amount_paid_cents', 'amount_outstanding_cents',
        'status', 'record_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date', 'delinquent_from' => 'date', 'cleared_at' => 'date', 'days_overdue' => 'integer',
            'amount_due_cents' => 'integer', 'amount_paid_cents' => 'integer', 'amount_outstanding_cents' => 'integer',
            'record_snapshot' => 'array',
        ];
    }

    public function organisation(): BelongsTo { return $this->belongsTo(Organisation::class); }
    public function borrower(): BelongsTo { return $this->belongsTo(Borrower::class); }
    public function loan(): BelongsTo { return $this->belongsTo(Loan::class); }
    public function loanSchedule(): BelongsTo { return $this->belongsTo(LoanSchedule::class); }
}
