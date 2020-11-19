<?php

namespace App\Concerns;

use App\Campaign;

trait CampaignConcerns
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}