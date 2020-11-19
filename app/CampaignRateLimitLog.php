<?php

namespace App;

use App\Concerns\CampaignConcerns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampaignRateLimitLog extends Model
{
    use CampaignConcerns;/*, SoftDeletes*/
    protected $table = 'campaign_rate_limit_log';

    protected $fillable = [
        'campaign_id',
        'variant',
        'payload',
        'schedule_date',
        'status'
    ];

    protected $hidden = ['id', 'created_at', 'updated_at'];

}