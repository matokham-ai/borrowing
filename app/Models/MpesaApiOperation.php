<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MpesaApiOperation extends Model
{
    protected $fillable = [
        'organisation_id',
        'loan_payment_id',
        'callback_token',
        'product',
        'environment',
        'status',
        'request_payload',
        'response_payload',
        'callback_payload',
        'provider_conversation_id',
        'provider_transaction_id',
        'error_message',
        'submitted_at',
        'completed_at',
    ];

    protected $hidden = [
        'request_payload',
        'response_payload',
        'callback_payload',
    ];

    protected function casts(): array
    {
        return [
            'request_payload' => 'encrypted:array',
            'response_payload' => 'encrypted:array',
            'callback_payload' => 'encrypted:array',
            'submitted_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }
}
