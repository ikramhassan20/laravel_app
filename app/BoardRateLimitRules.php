<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BoardRateLimitRules extends Model
{
    //
    protected $table = 'board_rate_limit';
    protected $fillable = [
        'board_id',
        'rate_limit',
        'duration_unit',
        'duration_value',
    ];

}
