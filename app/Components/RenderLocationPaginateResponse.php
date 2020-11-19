<?php
/**
 * Created by PhpStorm.
 * User: ets-rebel
 * Date: 1/23/19
 * Time: 5:31 PM
 */

namespace App\Components;


use App\Location;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class RenderLocationPaginateResponse
{

    public function renderpaginate(Request $request)
    {
        $locationLimit = config('enums.migration.popular_newsfeed_count_limit');
        $subQueryLocationCount = DB::table("news_feed")
            ->select("news_feed.location_id", DB::raw('count(news_feed.location_id) as total'))
            ->groupBy("news_feed.location_id");
        $user = $request->user();
        $group = $user->currentAppGroup();
        if ($request['sideFilters'] == 'Popular Locations') {
            $queryChain = Location::leftjoin(DB::raw('(' . $subQueryLocationCount->toSql() . ') as totalTable'), "location.id", "=", 'totalTable.location_id')
                ->whereNotNull("totalTable.location_id")
                ->where("totalTable.total", ">=", $locationLimit)
                ->where('location.deleted_at', Null);
        } else {
            $queryChain = Location::where('location.deleted_at', Null);
        }
        if ($user->is_admin == 0) {
            $queryChain = $queryChain->where('app_group_id', $group->id);
        }
        $totalCount = clone $queryChain;
        $totalCount = $totalCount->count();
        if ($request['sideFilters'] != null && $request['sideFilters'] != []) {
            if ($request['sideFilters'] == "Active") {
                $status = 1;
                $queryChain->where('is_active', '=', $status);
            } else if ($request['sideFilters'] == "Inactive") {
                $status = 0;
                $queryChain->where('is_active', '=', $status);
            }
        }
        if ($request['query'] != null) {
            $search = $request['query'];
            $columns = $request['columns'];
            $queryChain->where(function ($query) use ($search, $columns) {
                $query->where('code', 'LIKE', "%{$search}%");
                $query->orWhere('name', 'LIKE', "%{$search}%");
                $query->orWhere('description', 'LIKE', "%{$search}%");
            });
        }
        $totalFiltered = clone $queryChain;
        $totalFiltered = $totalFiltered->count();
        $orderBy = $request["orderBy"];
        if ($orderBy == "status") {
            $orderBy = "is_active";
        }
        isset($request["orderBy"]) ? $queryChain->orderBy($orderBy, $request["ascending"] == 1 ? 'desc' : 'asc') : '';
        $data = $queryChain->offset(($request['page'] - 1) * $request['limit'])
            ->limit($request['limit'])
            ->get([
                'location.id',
                'location.code',
                'location.name',
                'location.description',
                'location.is_active',
                'location.created_at'
            ]);

        $meta = [
            'pages' => ceil($totalFiltered / $request['limit']),
            'page' => $request['page'],
            'total' => $totalFiltered,
        ];
        for ($val = 0; $val < count($data); $val++) {
            if ($data[$val]['is_active'] == '1') {
                $data[$val]['status'] = 'Active';
            } else {
                $data[$val]['status'] = 'In Active';
            }
        }
        return [
            'meta' => $meta,
            'data' => $data
        ];
//
//        return $locationArray;
    }

    public function Lookupvalidator(Request $request)
    {
        $locationId = [];
        if ($request->input('id') != null) {
            $locationId[] = $request->input('id');
        }

        $group = $request->user()->currentAppGroup()->id;

        $data = Location::where('name', '=', $request->input('name'))
            ->whereNotIn('id', $locationId)
            ->where('app_group_id', $group)
            ->first();

        if ($data) {
            $flag = true;
        } else {
            $flag = false;
        }

        return $flag;
    }

}