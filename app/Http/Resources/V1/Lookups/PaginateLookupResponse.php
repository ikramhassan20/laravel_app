<?php

namespace App\Http\Resources\V1\Lookups;

use App\Lookup;
use Illuminate\Http\Request;
use mysql_xdevapi\Exception;

class PaginateLookupResponse
{
    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function process(Request $request)
    {

        $queryChain = Lookup::leftjoin('lookup as l2', 'l2.id', '=', 'lookup.parent_id')
            ->where('lookup.deleted_at', NULL);

        $totalCount = clone $queryChain;
        $totalCount = $totalCount->count();
        if ($request['sideFilters'] != null && $request['sideFilters'] != []) {
            if ($request['sideFilters']['value'] == 0) {
                $queryChain->where('lookup.parent_id', '=', 0);
            }
        } else {
            $queryChain->where('lookup.parent_id', '!=', 0);
        }
        if ($request['query'] != null) {
            $search = $request['query'];
            $columns = $request['columns'];
            $queryChain->where(function ($query) use ($search, $columns) {
                $query->where('lookup.code', 'LIKE', "%{$search}%");
                $query->orWhere('lookup.name', 'LIKE', "%{$search}%");
                $query->orWhere('l2.code', 'LIKE', "%{$search}%");
            });
        }
        $totalFiltered = clone $queryChain;
        $totalFiltered = $totalFiltered->count();
        isset($request["orderBy"]) ? $queryChain->orderBy($request["orderBy"], $request["ascending"] == 1 ? 'desc' : 'asc') : $queryChain->orderBy('lookup.updated_at', 'desc');
        $data = $queryChain->offset(($request['page'] - 1) * $request['limit'])
            ->limit($request['limit'])
            ->get([
                "l2.code as parent",
                "l2.id as parent_id",
                "lookup.id as id",
                "lookup.code as code",
                "lookup.name as name",
                "lookup.created_at as created_at"
            ]);

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

    public function getLookupbyId($id)
    {
        $lookup = Lookup::where('id', '=', $id)->first();
        return $lookup;
    }
}