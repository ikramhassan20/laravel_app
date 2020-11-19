<?php

namespace App;

use App\Components\ParseTableColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppUserTokens extends Model
{
    use ParseTableColumns, SoftDeletes;

    protected $table = 'app_user_token';

    protected $fillable = [
        "row_id",
        "user_id",
        "app_name",
        "app_id",
        "app_version",
        "app_build",
        "instance_id",
        "user_token",
        "device_token",
        "device_type",
        "lang",
        "is_logged_in",
        "is_revoked",
        "is_cache_sync"
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(AppUsers::class, 'row_id');
    }
}
