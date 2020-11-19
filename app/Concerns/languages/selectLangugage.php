<?php

namespace App\Concerns;

use Illuminate\Support\Facades\DB;


trait selectLangugage
{
    public static function searchForTheLanguages($search)
    {
        return DB::table("language")
            ->where("name", 'LIKE', "%{$search}%")
            ->select("id", "name as label", "code as value", "image as imgUrl", "dir")
            ->get();
    }
}