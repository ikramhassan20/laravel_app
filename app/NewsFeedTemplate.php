<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NewsFeedTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'content',
        'is_active',
        'created_by',
        'updated_by'
    ];
}
