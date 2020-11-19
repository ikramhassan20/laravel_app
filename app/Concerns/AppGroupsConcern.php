<?php

namespace App\Concerns;

use App\AppGroup;

trait AppGroupsConcern
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function app_group()
    {
        return $this->belongsTo(AppGroup::class);
    }
}