<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LocationArea extends Model
{
    use SoftDeletes;

    protected $table = 'location_areas';

    protected $fillable = [
        'location_id',
        'address',
        'latitude',
        'longitude',
        'radius'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function getlocationArea()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

}
