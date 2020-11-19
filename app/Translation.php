<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Translation extends Model
{
    //use SoftDeletes;
    protected $table='translation';
    protected $fillable = [
        'language_id',
        'translatable_id',
        'translatable_type',
        'template'
    ];

    protected $hidden = [
        'translatable_type',
        'deleted_at',
        'created_at',
        'updated_at'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function language()
    {
        return $this->belongsTo(Language::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function translatable()
    {
        return $this->morphTo();
    }
}
