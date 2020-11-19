<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NewsFeedImpression extends Model
{
    //
    use SoftDeletes;
    protected $table = 'news_feed_impression';
    protected $fillable = [
        'row_id',
        'user_id',
        'news_feed_id',
        'location_id',
        'platform',
        'viewed',
        'created_date'
    ];

    public function newsFeed()
    {
        return $this->belongsTo(NewsFeed::class, 'news_feed_id');
    }
}
