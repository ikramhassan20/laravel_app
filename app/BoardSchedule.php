<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BoardSchedule extends Model
{
    //
    protected $table = 'board_schedule';
    protected $fillable = [
        'campaign_id',
        'day',
    ];
    protected $hidden = ['deleted_at', 'updated_at', 'created_at'];
}
