<?php

namespace App\Models;

use App\Traits\MultiTenantTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class MpesaSetting extends Model
{
    use MultiTenantTrait;

    protected $fillable = [
        'organisation_id',
        'environment',
        'consumer_key',
        'consumer_secret',
        'passkey',
        'business_shortcode',
        'head_office',
        'store_number',
        'till_number',
        'g2_password',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'consumer_key' => 'encrypted',
            'consumer_secret' => 'encrypted',
            'passkey' => 'encrypted',
            'g2_password' => 'encrypted',
            'is_active' => 'boolean',
        ];
    }

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }
}
