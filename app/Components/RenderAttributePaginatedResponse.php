<?php

namespace App\Components;

use App\Attribute;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

trait RenderAttributePaginatedResponse
{
    /**
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */

    public function attributePaginateResponse($model, Request $request)
    {
        $queryChain = DB::table('attribute')
            ->where('app_group_id', $request->user()->currentAppGroup()->id)->where('deleted_at', NULL);

        if ($request->user()->currentAppGroup()->id == 1) {
            $excludedAttributesIDs = DB::select("SELECT id FROM `attribute` WHERE
            `app_group_id` = 1
                AND level_type = 'platform'
                AND deleted_at IS NULL");
            $excludedAttributesIDs = array_map(function ($data) {
                return $data->id;
            }, $excludedAttributesIDs);

            $queryChain->whereNotIn('id', $excludedAttributesIDs);
        }

        $totalCount = clone $queryChain;
        $totalCount = $totalCount->count();
        if ($request['sideFilters'] != null && $request['sideFilters'] != []) {
            if (isset($request['sideFilters']['column']) AND $request['sideFilters']['value']) {
                $queryChain->where($request['sideFilters']['column'], $request['sideFilters']['value']);
            }
            //   $queryChain->where('lookups'.'.'.$request['sideFilters']['parent'],'=', $request['sideFilters']['value']);
        }

        if ($request['query'] != null) {
            $search = $request['query'];
            $columns = $request['columns'];
            $queryChain->where(function ($query) use ($search, $columns) {
                $query->orWhere('attribute.id', 'LIKE', "%{$search}%");
                $query->orWhere('attribute.name', 'LIKE', "%{$search}%");
                $query->orWhere('attribute.code', 'LIKE', "%{$search}%");
                $query->orWhere('attribute.data_type', 'LIKE', "%{$search}%");
                $query->orWhere('attribute.length', 'LIKE', "%{$search}%");
                $query->orWhere('attribute.attribute_type', 'LIKE', "%{$search}%");
                $query->orWhere('attribute.level_type', 'LIKE', "%{$search}%");
                $query->orWhere('attribute.created_at', 'LIKE', "%{$search}%");
            });
        }
        $totalFiltered = clone $queryChain;
        $totalFiltered = $totalFiltered->count();
        isset($request["orderBy"]) ? $queryChain->orderBy($request["orderBy"], $request["ascending"] == 1 ? 'desc' : 'asc') : $queryChain->orderBy('updated_at', 'DESC');
        $data = $queryChain->offset(($request['page'] - 1) * $request['limit'])
            ->limit($request['limit'])
            ->get();
        $meta = [
            'pages' => ceil($totalFiltered / $request['limit']),
            'page' => $request['page'],
            'total' => $totalFiltered,
        ];
        return [
            'meta' => $meta,
            'data' => $data
        ];
    }

}