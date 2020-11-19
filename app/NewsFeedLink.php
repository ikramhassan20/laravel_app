<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NewsFeedLink extends Model
{
    protected $fillable = [
        'news_feed_id',
        'link_platform',
        'link_type',
        'link_value',
        'link_text',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function newsfeed()
    {
        return $this->belongsTo(NewsFeed::class);
    }
}
