<?php

namespace App\Http\Resources\V1\Campaigns;

use App\Campaign;
use App\Concerns\exportUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Psr\Log\NullLogger;

class campaignPaginateResponse
{
    use exportUsers;

    public function process($request)
    {
//        $subQueryTotal = DB::table("campaign_tracking")
//            ->groupBy("campaign_tracking.campaign_id")
//            ->select("campaign_tracking.campaign_id", DB::raw('count(campaign_tracking.campaign_id) as total'));
//
//
//        $subQuerySent = DB::table("campaign_tracking")
//            ->where("campaign_tracking.sent", "<>", 0)
//            ->groupBy("campaign_tracking.campaign_id")
//            ->select("campaign_tracking.campaign_id", DB::raw('count(campaign_tracking.campaign_id) as sent'));
//
//        $subQueryView = DB::table("campaign_tracking")
//            ->where("campaign_tracking.viewed", "<>", 0)
//            ->groupBy("campaign_tracking.campaign_id")
//            ->select("campaign_tracking.campaign_id", DB::raw('count(campaign_tracking.campaign_id) as view'));
//
//        $subQueryFailed = DB::table("campaign_tracking")
//            ->where("campaign_tracking.status", "failed")
//            ->groupBy("campaign_tracking.campaign_id")
//            ->select("campaign_tracking.campaign_id", DB::raw('count(campaign_tracking.campaign_id) as failed'));


        $queryChain = DB::table("campaign as c1")
            //->join("app_group", "c1.app_group_id", "=", "app_group.id")
//            ->leftJoin(DB::raw('(' . $subQueryTotal->toSql() . ') as totalTable'), "c1.id", "=", 'totalTable.campaign_id')
//            ->leftJoin(DB::raw('(' . $subQuerySent->toSql() . ') as sentTable'), "c1.id", "=", 'sentTable.campaign_id')->addBinding($subQuerySent->getBindings())
//            ->leftJoin(DB::raw('(' . $subQueryView->toSql() . ') as viewTable'), "c1.id", "=", 'viewTable.campaign_id')->addBinding($subQueryView->getBindings())
//            ->leftJoin(DB::raw('(' . $subQueryFailed->toSql() . ') as failTable'), "c1.id", "=", 'failTable.campaign_id')->addBinding($subQueryFailed->getBindings())
            //->where("app_group.company_id", "=", $request->user()->id);
            ->where("app_group_id", "=", $request->user()->currentAppGroup()->id);

        $totalCount = clone $queryChain;
        $totalCount = $totalCount->count();

        if ($request['sideFilters'] != null && $request['sideFilters'] != [] && $request['sideFilters'] != "") {
            if ($request['sideFilters']['parent'] == "tags") {
                $value = $request['sideFilters']['value'];
                $column = $request['sideFilters']['parent'];
                $queryChain->whereRaw("FIND_IN_SET('$value', BINARY $column) > 0");
            } else {
                $queryChain->where($request['sideFilters']['parent'], $request['sideFilters']['value']);
            }
        }
        if ($request['query'] != null) {
            $search = $request['query'];
            $columns = $request['columns'];
            $queryChain->where(function ($query) use ($search, $columns) {
                $query->where("c1.name", 'LIKE', "%{$search}%")
                    ->orWhere("c1.campaign_type", 'LIKE', "%{$search}%")
                    ->orWhere("c1.status", 'LIKE', "%{$search}%")
                    ->orWhere("c1.updated_at", 'LIKE', "%{$search}%")
                    ->orWhere("total", 'LIKE', "%{$search}%")
                    ->orWhere("sent", 'LIKE', "%{$search}%")
                    ->orWhere("view", 'LIKE', "%{$search}%")
                    ->orWhere("failed", 'LIKE', "%{$search}%");
            });
        }

        $totalFiltered = clone $queryChain;
        $totalFiltered = $totalFiltered->count();

        isset($request["orderBy"]) ? $queryChain->orderBy($request["orderBy"], $request["ascending"] == 1 ? 'desc' : 'asc') : $queryChain->orderBy('updated_at', 'desc');
        $data = $queryChain->offset(($request['page'] - 1) * $request['limit'])
            ->limit($request['limit'])
            ->select("c1.id", "c1.name", "c1.app_group_id", "c1.campaign_type", "c1.status", "c1.updated_at", "c1.schedule_type", "c1.priority")
//                DB::raw('IFNULL(totalTable.total, 0) as total'),
//                DB::raw('IFNULL(sentTable.sent, 0) as sent'),
//                DB::raw('IFNULL(viewTable.view, 0) as view'),
//                DB::raw('IFNULL(failTable.failed, 0) as failed')
            ->get();

//        foreach ($data as $obj) {
//            $obj->targeted_users = exportUsers::exportUsers($obj->id, 'campaign', $obj->app_group_id, true);
//        }

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