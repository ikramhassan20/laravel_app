<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LinkTrackings extends Model
{
    //
    use  SoftDeletes;
    protected $table = 'link_tracking';
    protected $fillable = [
        'rec_type',
        'rec_id',
        'row_id',
        'actual_url',
        'created_date',
        'ip_address',
        'user_agent',
        'device_type',
        'viewed',
        'is_board'
    ];

    public function newsfeed()
    {
        return $this->belongsTo(NewsFeed::class, 'rec_id');
    }
}
