<?php

namespace App\Models;

use App\Traits\MultiTenantTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use MultiTenantTrait;

    protected $fillable = [
        'organisation_id', 'user_id', 'borrower_id', 'channel', 'notification_type', 'title', 'message',
        'data', 'read_at', 'sent_at', 'failed_at', 'failure_reason', 'status', 'reference_type', 'reference_id',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array', 'read_at' => 'datetime', 'sent_at' => 'datetime', 'failed_at' => 'datetime',
            'reference_id' => 'integer',
        ];
    }

    public function organisation(): BelongsTo { return $this->belongsTo(Organisation::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function borrower(): BelongsTo { return $this->belongsTo(Borrower::class); }
    public function reference(): MorphTo { return $this->morphTo(); }
}
