<?php

namespace App;

//use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles, Notifiable;

    const role = "SUPER-ADMIN";

    protected $dates = [
        'created_at',
        'updated_at'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_key',
        'name',
        'email',
        'password',
        'logo',
        'timezone',
        'is_active',
        'remember_token',
        'api_token',
        'created_by',
        'updated_by',
        'last_login',
        'cache_status'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be appends for arrays.
     *
     * @var array
     */
    protected $appends = [
        'is_admin'
    ];

    /**
     * Route notifications for the mail channel.
     *
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return string
     */
    public function routeNotificationForMail($notification)
    {
        return $this->email;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function email_setting()
    {
        return $this->hasOne(UserEmailSettings::class, 'company_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function app_groups()
    {
        return $this->hasMany(AppGroup::class, 'company_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function defaultAppGroup()
    {
        return $this->app_groups->filter(function ($app_group) {
            return ($app_group->isDefault() === true) ? $app_group : null;
        })->first();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function currentAppGroup()
    {
        return $this->app_groups->filter(function ($app_group) {
            return ($app_group->isDefault() === true) ? $app_group : null;
        })->first();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function campaigns()
    {
        return $this->hasMany(Campaign::class, 'company_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attributes()
    {
        return $this->hasMany(Attribute::class, 'company_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attribute_data()
    {
        return $this->hasMany(AttributeData::class, 'company_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function apps_users()
    {
        return $this->hasMany(AppUsers::class, 'company_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function newsfeeds()
    {
        return $this->hasMany(NewsFeed::class, 'company_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\belongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_has_roles');
    }

    public function getIsAdminAttribute()
    {
        return $this->roles()->where('name', self::role)->count();
    }

    public function getLogoAttribute()
    {
        $logo = $this->attributes['logo'];
        if ($logo) {
            return $logo;
        }

        return asset('assets/images/'.\Config::get("app.logo"));
    }
}
