<?php

namespace App;

use App\Concerns\AppGroupsConcern;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Apps extends Model
{
    use AppGroupsConcern, SoftDeletes;

    protected $table = 'app';
    protected $appends = ['logoUrl'];

    protected $fillable = [
        'app_group_id',
        'name',
        'logo',
        'app_id',
        'description',
        'firebase_api_key',
        'platform',
        'is_active',
        'code'
    ];

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    /*protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new \App\Scopes\AppGroupScope());
    }*/

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function campaigns()
    {
        return $this->belongsToMany(Campaign::class, 'campaigns_apps', 'campaign_id', 'app_id');
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return ((bool)$this->is_active === true) ? true : false;
    }

    public function getLogoUrlAttribute()
    {
        return asset("storage/uploads/app/{$this->logo}");
    }

    public function group()
    {
        return $this->belongsTo(AppGroup::class, 'app_group_id');
    }
}
