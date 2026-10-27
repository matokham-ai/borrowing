<?php

namespace App\Models;

use App\Traits\MultiTenantTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class MpesaTransactionAttempt extends Model
{
    use MultiTenantTrait;

    protected $fillable = [
        'organisation_id',
        'mpesa_transaction_id',
        'attempt_number',
        'status',
        'request_reference',
        'response_code',
        'response_description',
        'request_payload',
        'response_payload',
        'submitted_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'attempt_number' => 'integer',
            'request_payload' => 'array',
            'response_payload' => 'array',
            'submitted_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    public function mpesaTransaction(): BelongsTo
    {
        return $this->belongsTo(MpesaTransaction::class, 'mpesa_transaction_id');
    }
}
