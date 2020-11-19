<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CampaignTracking extends Model
{
    //
    protected $table = 'campaign_tracking';

    protected $fillable = [
        'campaign_id',
        'row_id',
        'app_user_token_id',
        'variant_id',
        'language_id',
        'email',
        'firebase_key',
        'device_key',
        'device_type',
        'payload',
        'track_key',
        'job',
        'status',
        'message',
        'sent',
        'viewed',
        'started_at',
        'ended_at',
        'sent_at',
        'viewed_at',
        'created_at',
        'updated_at'
    ];


    public function campaign_type()
    {
        return $this->hasOne(Campaign::class, 'campaign_type_id', 'id');
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class, 'campaign_id');
    }

    public function app_user()
    {
        return $this->belongsTo(AppUsers::class, 'row_id');
    }

    public function campaign_tracking_log()
    {
        return $this->hasOne(CampaignTrackingLog::class, "campaign_tracking_id");
    }
}
