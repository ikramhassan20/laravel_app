<?php
/**
 * Created by PhpStorm.
 * User: omair
 * Date: 2019-01-28
 * Time: 15:08
 */

namespace App;


use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    protected $table = 'templates';
    protected $appends = ['fullThumbnail'];
    protected $fillable = [
        'code',
        'name',
        'type',
        'thumbnail',
        'content_url',
        'type',
        'is_active',
        'created_at',
        'updated_at'
    ];

    const TYPE_EMAIL = 'email';
    const TYPE_PUSH = 'push';
    const TYPE_INAPP = 'inapp';
    const NEWS_FEED = 'newsfeed';

    public function getFullThumbnailAttribute()
    {
        return asset("{$this->thumbNail}");
    }

}
