<?php

namespace App\Models;

use App\Traits\MultiTenantTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class BehaviourEvent extends Model
{
    use MultiTenantTrait;

    protected $fillable = [
        'organisation_id', 'borrower_id', 'loan_id', 'loan_schedule_id', 'loan_payment_id',
        'event_type', 'score_impact', 'occurred_at', 'idempotency_key', 'event_data',
    ];

    protected function casts(): array
    {
        return ['score_impact' => 'decimal:4', 'occurred_at' => 'datetime', 'event_data' => 'array'];
    }

    public function organisation(): BelongsTo { return $this->belongsTo(Organisation::class); }
    public function borrower(): BelongsTo { return $this->belongsTo(Borrower::class); }
    public function loan(): BelongsTo { return $this->belongsTo(Loan::class); }
    public function loanSchedule(): BelongsTo { return $this->belongsTo(LoanSchedule::class); }
    public function loanPayment(): BelongsTo { return $this->belongsTo(LoanPayment::class); }
}
