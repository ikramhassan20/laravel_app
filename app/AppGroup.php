<?php

namespace App;

use App\Concerns\AppGroupDetailsConcern;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppGroup extends Model
{
    use AppGroupDetailsConcern;

    protected $table = 'app_group';

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'logo',
        'description',
        'is_default',
        'created_by',
        'updated_by',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company()
    {
        return $this->belongsTo(User::class, 'company_id');
    }

    /**
     * @return bool
     */
    public function isDefault()
    {
        return ((bool)$this->is_default === true) ? true : false;
    }

    public function segments()
    {
        return $this->hasMany(Segment::class, 'app_group_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function cappingrules()
    {
        return $this->hasMany(CampaignCapRule::class, 'app_group_id');
    }

    public function locations()
    {
        return $this->hasMany(Location::class, 'app_group_id');
    }

    public function newsFeeds()
    {
        return $this->hasMany(NewsFeed::class, 'app_group_id');
    }

    public function campaigns()
    {
        return $this->hasMany(Campaign::class, 'app_group_id');
    }

    public function boards()
    {
        return $this->hasMany(Board::class, 'app_group_id');
    }
}
