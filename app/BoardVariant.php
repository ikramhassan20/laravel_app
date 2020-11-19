<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BoardVariant extends Model
{

    protected $table = "board_variant";

    const VARIANT_INAPP_CODE = 'InApp';
    const VARIANT_EMAIL_CODE = 'Email';
    const VARIANT_PUSH_CODE = 'Push';

    public function steps()
    {
        return $this->hasMany('App\BoardVariantStep', 'variant_id', 'id');
    }


}
