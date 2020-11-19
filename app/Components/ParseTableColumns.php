<?php

namespace App\Components;

trait ParseTableColumns
{
    /**
     * @param array $attributes
     *
     * @return array
     */
    public static function parseAttributes($attributes)
    {
        $fillables = (new static())->fillable;
        return collect($attributes)->filter(function ($value, $key) use($fillables) {
            return in_array($key, $fillables) ? $value : '';
        })->toArray();
    }
}