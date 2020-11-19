<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Language extends Model
{
    use SoftDeletes;
    protected $table='language';
    const DEFAULT_LANGUAGE = 'English';

    protected $fillable = [
        'name',
        'code',
        'image'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function translations()
    {
        return $this->hasMany(Translation::class);
    }

    public static function getAllLanguages()
    {
        $lang = [];
        $languages = self::where('code', '!=', '')->select('id', 'code')->get()->toArray();
        foreach($languages as $language){
            $lang[$language['code']] = $language['id'];
        }

        return $lang;

    }
}
