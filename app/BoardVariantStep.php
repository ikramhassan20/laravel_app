<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BoardVariantStep extends Model
{

    protected $table = "board_variant_step";

    public function variant()
    {
        return $this->belongsTo('App\BoardVariant', 'variant_id');
    }

    public static function getVariantNextStepId($variantId, $previousStepId = 0)
    {
        $variantStep = self::where('variant_id', '=', $variantId)
            ->where('id', '>', $previousStepId)
            ->orderBy('id', 'asc')
            ->select('id')
            ->first();

        if($variantStep){
            return $variantStep->id;
        }
        else{
            return NULL;
        }


    }

    public function trackingRowIds()
    {
        return $this->hasMany('App\BoardTracking', 'variant_step_id', 'id');
    }

}