<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ExpiredCampaignTracking extends Model
{
    protected $table = 'expired_campaign_tracking';

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
}
