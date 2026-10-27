<?php

namespace App\Models;

use App\Traits\MultiTenantTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class BehaviourScore extends Model
{
    use MultiTenantTrait;

    protected $fillable = [
        'organisation_id', 'borrower_id', 'score', 'repayment_score', 'timeliness_score',
        'consistency_score', 'discipline_score', 'model_version', 'observation_count',
        'score_snapshot', 'calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:4', 'repayment_score' => 'decimal:4', 'timeliness_score' => 'decimal:4',
            'consistency_score' => 'decimal:4', 'discipline_score' => 'decimal:4',
            'observation_count' => 'integer', 'score_snapshot' => 'array', 'calculated_at' => 'datetime',
        ];
    }

    public function organisation(): BelongsTo { return $this->belongsTo(Organisation::class); }
    public function borrower(): BelongsTo { return $this->belongsTo(Borrower::class); }
}
