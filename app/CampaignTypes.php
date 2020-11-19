<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class CampaignTypes extends Model
{
    use  SoftDeletes;
    protected $table = 'campaign_types';
    protected $fillable = [
        'name'
    ];
}
