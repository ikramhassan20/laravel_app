<?php

namespace App\Concerns;

use Illuminate\Support\Facades\DB;


trait tagsCount
{
    public static function findTagsCount($appGroupId, $table)
    {
        $query = "select x2.*";
        $query .= "from (";
        $query .= "select x1.tags, count(x1.tags) as total ";
        $query .= "from (";
        $query .= "select " . $table . ".app_group_id, SUBSTRING_INDEX(SUBSTRING_INDEX( " . $table . ".tags, ',', numbers.n), ',', -1) tags ";
        $query .= "from ";
        $query .= "(select 1 n union all ";
        $query .= "select 2 union all select 3 union all ";
        $query .= "select 4 union all select 5) numbers INNER JOIN " . $table;
        $query .= " on CHAR_LENGTH( " . $table . ".tags) ";
        $query .= "-CHAR_LENGTH(REPLACE( " . $table . ".tags, ',', ''))>=numbers.n-1 ";
        $query .= "order by ";
        $query .= "id, n ";
        $query .= ") as x1 ";
        $query .= "where x1.app_group_id = " . $appGroupId;
        $query .= " group by x1.tags ";
        $query .= ") as x2 ";
        $query .= "order by x2.total desc  ";
        $query .= "limit 11 ";

        $result = DB::select($query);


        for ($i = 0; $i < sizeof($result); $i++) {
            if ($result[$i]->tags == "") {
                unset($result[$i]);
            }
        }

        $totalLength = sizeof($result);
        if ($totalLength == 11) {
            unset($result[$totalLength - 1]);
        }

        return $result;
    }
}