<?php

namespace App;

use App\Cache\CacheKeys;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Cache\AppGroupSegmentCache;

class Segment extends Model
{
    use CompileTags;

    const SEGMENT_TYPE_USER = 'user';
    const SEGMENT_TYPE_ACTION = 'action';
    const SEGMENT_TYPE_CONVERSION = 'conversion';
    protected $table = 'segment';
    protected $fillable = [
        'app_group_id',
        'name',
        'tags',
        'criteria',
        'rules',
        'attribute_fields',
        'action_fields',
        'conversion_fields',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $hidden = ['criteria', 'deleted_at', 'updated_at', 'created_at', 'created_by', 'updated_by'];

    protected $appends = ['row_count'];

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new \App\Scopes\AppGroupScope());
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function campaigns()
    {
        return $this->belongsToMany(Campaign::class, 'campaign_segment');
    }

    public function getRulesAttribute()
    {
        return json_decode($this->attributes['rules']);
    }

    /*public function getRowCountAttribute()
    {
        if (isset($this->attributes['app_group_id'])) {
            $cache = new CacheKeys($this->attributes['app_group_id']);
            $key = $cache->generateAppGroupSegmentRowsKey($this->attributes['id']);

            if (\Cache::has($key)) {
                $data = \Cache::get($key);

                return count(json_decode($data));
            }
        }
    }*/

    public function getRowCountAttribute()
    {
        if (isset($this->attributes['app_group_id'])) {
            $segment_cache = new AppGroupSegmentCache();
            return $segment_cache->getSegmentRowsCount($this->attributes['app_group_id'], $this->attributes['id']);
        }
    }
}
