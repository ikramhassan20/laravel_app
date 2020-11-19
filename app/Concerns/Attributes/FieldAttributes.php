<?php

namespace App\Concerns;

use Illuminate\Support\Facades\DB;

trait FieldAttributes
{
    public static function segmentAttributeFields($appGroupId)
    {
        $queryString = 'SELECT DISTINCT * FROM attribute as a1 ';
        $queryString .= 'WHERE (a1.deleted_at is null && (a1.app_group_id = ' . $appGroupId . ' OR a1.level_type = "platform")) AND Not EXISTS ( ';
        $queryString .= 'SELECT * ';
        $queryString .= 'FROM attribute as a2 ';
        $queryString .= 'WHERE a2.level_type = "platform" AND a1.code = a2.code AND a1.level_type != a2.level_type )';
        return DB::Select($queryString);
    }

    public static function getValuesAgainstSelectAttribute($field, $companyId)
    {
        return [];

        $parentId = DB::table($field->source_table_name)
            ->where('company_id', $companyId)
            ->where('name', $field->code)
            ->first()
            ->id;

        if (!$field->text_column) {

            $list = DB::table($field->source_table_name)
                ->where('parent_id', $parentId)
                ->pluck('name');

        } else {

            $query = 'select name from ' . $field->source_table_name . ' where parent_id=' . $parentId . ' and ' . ' ( ' . $field->where_condition . ' ) ';
            $results = DB::select(DB::raw($query));
            $list = array();
            foreach ($results as $result) {
                $list[] = $result->name;
            }
        }
        return $list;
    }

}