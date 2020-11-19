<?php

namespace App;

use App\Components\ParseTableColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppUsers extends Model
{
    use ParseTableColumns, SoftDeletes;

    const USER_REGISTER = 'register';
    const USER_LOGIN = 'login';
    const USER_UPDATE = 'update';
    const USER_IMPORT = 'import';
    const USER_IMPORT_API = 'import_api';
    const USER_REBUILD_CACHE = 'rebuild_cache';
    const USER_LOGOUT = 'logout';
    const USER_REVOKED = 'is_revoked';

    protected $table = 'app_user';
    protected $primaryKey = 'row_id';

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    protected $fillable = [
        "row_id",
        "app_group_id",
        "company_id",
        "app_id",
        "user_id",
        "username",
        "firstname",
        "lastname",
        "email",
        "image_url",
        "timezone",
        "latitude",
        "longitude",
        "country",
        "last_login",
        "enabled",
        "enable_notification",
        "email_notification",
        "is_deleted"
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tokens()
    {
        return $this->hasMany(AppUserTokens::class, 'row_id');
    }

    public function activities()
    {
        return $this->hasMany(AppUserActivity::class, 'row_id');
    }

    public function company()
    {
        return $this->belongsTo(User::class, 'company_id');
    }

    public function group()
    {
        return $this->belongsTo(AppGroup::class, 'app_group_id');
    }
}
