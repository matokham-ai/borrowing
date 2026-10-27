<?php

namespace App\Models;

use App\Traits\MultiTenantTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class KycRecord extends Model
{
    use MultiTenantTrait;

    protected $fillable = [
        'organisation_id', 'borrower_id', 'document_type', 'document_number', 'document_country_code',
        'full_name', 'date_of_birth', 'verification_status', 'verification_provider', 'verification_reference',
        'verified_at', 'verification_notes', 'document_file_key', 'verification_snapshot', 'is_current',
    ];

    protected function casts(): array
    {
        return ['date_of_birth' => 'date', 'verified_at' => 'datetime', 'verification_snapshot' => 'array', 'is_current' => 'boolean'];
    }

    public function organisation(): BelongsTo { return $this->belongsTo(Organisation::class); }
    public function borrower(): BelongsTo { return $this->belongsTo(Borrower::class); }
}
