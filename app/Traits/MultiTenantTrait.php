<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait MultiTenantTrait
{
    /**
     * Boot the trait to automatically intercept all queries and inserts.
     */
    protected static function bootMultiTenantTrait(): void
    {
        // Automatically inject organisation_id when creating new records
        static::creating(function ($model) {
            if (Auth::check() && empty($model->organisation_id)) {
                $model->organisation_id = Auth::user()->organisation_id;
            }
        });

        // Automatically scope all read/update/delete operations
        static::addGlobalScope('organisation', function (Builder $builder) {
            if (Auth::check()) {
                $builder->where(
                    $builder->getQuery()->from . '.organisation_id',
                    Auth::user()->organisation_id
                );
            }
        });
    }
}