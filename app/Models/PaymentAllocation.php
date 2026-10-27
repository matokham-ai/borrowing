<?php

namespace App\Models;

use App\Traits\MultiTenantTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class PaymentAllocation extends Model
{
    use MultiTenantTrait;

    protected $fillable = ['organisation_id', 'loan_payment_id', 'loan_schedule_id', 'allocation_type', 'amount_cents'];

    protected function casts(): array
    {
        return ['amount_cents' => 'integer'];
    }

    public function organisation(): BelongsTo { return $this->belongsTo(Organisation::class); }
    public function loanPayment(): BelongsTo { return $this->belongsTo(LoanPayment::class); }
    public function loanSchedule(): BelongsTo { return $this->belongsTo(LoanSchedule::class); }
}
