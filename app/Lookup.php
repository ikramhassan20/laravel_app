<?php

namespace App;

use App\Concerns\AppGroupsConcern;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lookup extends Model
{
    protected  $table='lookup';
    const LOOKUP_CODE_PLATFORM = "Platform";
    const LOOKUP_CODE_MESSAGE_TYPE = "Message_Type";
    const LOOKUP_CODE_BANNER = 'banner';
    const LOOKUP_CODE_LAYOUT = "Layout";
    const LOOKUP_CODE_DEVICE_POSITION = "Device_Position";
    const LOOKUP_CODE_ACTION = "Action";
    const LOOKUP_CODE_CONVERSION_TYPE = "CONVERSION_TYPES";
    const LOOKUP_CODE_CONVERSION = "conversion";
    const LOOKUP_CODE_ACTION_TRIGGERS = "ACTION_TRIGGERS";

    use AppGroupsConcern, SoftDeletes;

    protected $fillable = [
        'app_group_id',
        'code',
        'name',
        'level',
        'description',
        'parent_id',
        'created_by',
        'updated_by',
        'deleted_at'
    ];

    /**
     * @param string $value
     * @return string
     */
    public function getCode($value)
    {
        return strtolower($value);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
