<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NewsFeed extends Model
{
    protected $table = 'news_feed';
    const STEP_COMPOSE = 'compose';
    const STEP_DELIVERY = 'delivery';
    const STEP_PREVIEW = 'confirm';

    const status_draft = 'draft';
    const status_active = 'active';
    const status_suspend = 'suspend';

    const STEP_LEVEL = ['compose', 'delivery', 'preview'];

    use SoftDeletes;

    protected $fillable = [
        'app_group_id',
        'segment_id',
        'location_id',
        'news_feed_template_id',
        'name',
        'title',
        'image_url',
        'tags',
        'content',
        'category',
        'step',
        'publish_now',
        'start_time',
        'end_time',
        'is_active',
        'created_by',
        'updated_by'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function links()
    {
        return $this->hasMany(NewsFeedLink::class);
    }

}
