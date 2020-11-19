<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VariantStep extends Model
{
    //
    protected $table = "board_variant_step";

    public function trackingRowIds()
    {
        return $this->hasMany('App\BoardTracking', 'variant_step_id', 'id');
    }
}
