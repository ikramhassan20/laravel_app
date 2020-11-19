<?php

namespace App;

use App\Concerns\AppGroupsConcern;
use App\Concerns\CampaignConcerns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampaignFilter extends Model
{
    use CampaignConcerns, SoftDeletes;

    protected $table = 'campaign_filter';
    protected $fillable = [
        'campaign_id',
        'rules',
        'criteria',
        'filter_type',
    ];

}
