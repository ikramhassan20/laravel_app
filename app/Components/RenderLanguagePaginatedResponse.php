<?php

namespace App\Components;

use App\Language;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

trait RenderLanguagePaginatedResponse
{
    /**
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */

    public function languagePaginateResponse($model, Request $request)
    {
        $queryChain = DB::table('language')->where('deleted_at',NULL);
        $totalCount = clone $queryChain;
        $totalCount = $totalCount->count();
        if ($request['sideFilters'] != null && $request['sideFilters'] != []) {
         //   $queryChain->where('lookups'.'.'.$request['sideFilters']['parent'],'=', $request['sideFilters']['value']);
        }

        if ($request['query'] != null) {
            $search = $request['query'];
            $columns = $request['columns'];
            $queryChain->where(function ($query) use ($search, $columns) {
                $query->where('languages.id', 'LIKE', "%{$search}%");
                $query->orWhere('languages.name', 'LIKE', "%{$search}%");
                $query->orWhere('languages.code', 'LIKE', "%{$search}%");
            });
        }
        $totalFiltered = clone $queryChain;
        $totalFiltered = $totalFiltered->count();
        isset($request["orderBy"]) ? $queryChain->orderBy($request["orderBy"], $request["ascending"] == 1 ? 'desc' : 'asc') : $queryChain->orderBy('updated_at', 'desc');
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