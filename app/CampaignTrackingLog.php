<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CampaignTrackingLog extends Model
{
    //
    protected $table = 'campaign_tracking_log';
    protected $fillable = [
        'campaign_tracking_id',
        'status',
        'message'
    ];

}
