<?php

namespace App;

use App\Traits\CommonTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Gallery extends Model
{
    use SoftDeletes, CommonTrait;

    protected $table = 'gallery';

    protected $fillable = [
        'company_id',
        'image_url',
        'image_name',
        'image_height',
        'image_width',
        'image_size',
        'created_by',
        'is_active',
        'updated_by'
    ];

    public function getImageSizeAttribute()
    {
        return self::getFileSize($this->attributes['image_size']);
    }
}
