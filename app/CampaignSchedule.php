<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CampaignSchedule extends Model
{
    protected $table = 'campaign_schedule';

    protected $fillable = [
        'campaign_id',
        'day',
    ];
    protected $hidden = ['deleted_at', 'updated_at', 'created_at'];
}
