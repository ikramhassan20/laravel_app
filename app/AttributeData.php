<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttributeData extends Model
{
    protected $table = 'attribute_data';

    protected $fillable = [
        'company_id',
        'row_id',
        'code',
        'value',
        'created_by',
        'updated_by',
        'data_type',
    ];
}
