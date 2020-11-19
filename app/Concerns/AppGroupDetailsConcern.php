<?php

namespace App\Concerns;

use App\Apps;
use App\Campaign;
use App\Lookup;
use App\Segment;

trait AppGroupDetailsConcern
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function apps()
    {
        return $this->hasMany(Apps::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function segments()
    {
        return $this->hasMany(Segment::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function lookups()
    {
        return $this->hasMany(Lookup::class);
    }
}