<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppUserActivity extends Model
{
    //
//    use  SoftDeletes;
    protected $table = 'app_user_activity';
    protected $fillable = [
        'row_id',
        'campaign_id',
        'campaign_code',
        'track_key',
        'event_id',
        'event_value',
        'device_type',
        'build',
        'version',
        'rec_type'
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}
