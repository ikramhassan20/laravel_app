<?php

namespace App\Concerns;

use App\AttributeData;
use Illuminate\Support\Facades\DB;


trait selectUsers
{
    public static function selectUsersBySearch($appGroupId, $searching, $campaignType, $deviceType)
    {
        $queryChain = DB::table("app_user as a1")
            ->leftJoin("app_user_token as a2", "a1.row_id", "=", "a2.row_id")
            ->where("a1.app_group_id", $appGroupId)
            ->where('a1.email', 'LIKE', "%{$searching}%")
            ->where('a1.is_deleted', 0);

        if ($campaignType == "notEmail" && strtolower($deviceType) != "universal") {
            $queryChain->where(function ($query) use ($deviceType) {
                $query->where('a2.device_type', $deviceType);
            });
        }

        $users = $queryChain->distinct("a1.email", "a1.row_id", "a2.app_name", "a1.user_id")
            ->select("a1.row_id as value", DB::raw("CONCAT(a1.email, '(', a1.user_id, ') - ', a2.app_name) AS label"))
            ->get()->toArray();

        return $users;
    }
}