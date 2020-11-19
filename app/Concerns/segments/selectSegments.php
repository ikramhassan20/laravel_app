<?php

namespace App\Concerns;

use Illuminate\Support\Facades\DB;


trait selectSegments
{
    public static function searchForTheSegments($appGroupId, $search)
    {
        return DB::table("segment")
            ->where("app_group_id", $appGroupId)
            ->where('is_active', 1)
            ->where("name", 'LIKE', "%{$search}%")
            ->select("name as label", "id as value")
            ->get();
    }
}