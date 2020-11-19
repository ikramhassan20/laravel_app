<?php
namespace App\Http\Resources\V1\Boards;


use App\Concerns\exportUsers;
use Illuminate\Support\Facades\DB;

class boardPaginateResponse
{

    public function process($request)
    {
//        $subQueryTotal = DB::table("board_tracking")
//            ->groupBy("board_tracking.board_id")
//            ->select("board_tracking.board_id", DB::raw('count(board_tracking.board_id) as total'));
//
//
//        $subQuerySent = DB::table("board_tracking")
//            ->where("board_tracking.sent", "<>", 0)
//            ->groupBy("board_tracking.board_id")
//            ->select("board_tracking.board_id", DB::raw('count(board_tracking.board_id) as sent'));
//
//        $subQueryView = DB::table("board_tracking")
//            ->where("board_tracking.viewed", "<>", 0)
//            ->groupBy("board_tracking.board_id")
//            ->select("board_tracking.board_id", DB::raw('count(board_tracking.board_id) as view'));
//
//        $subQueryFailed = DB::table("board_tracking")
//            ->where("board_tracking.status", "failed")
//            ->groupBy("board_tracking.board_id")
//            ->select("board_tracking.board_id", DB::raw('count(board_tracking.board_id) as failed'));


        $queryChain = DB::table("board as c1")
            //->join("app_group", "c1.app_group_id", "=", "app_group.id")
//            ->leftJoin(DB::raw('(' . $subQueryTotal->toSql() . ') as totalTable'), "c1.id", "=", 'totalTable.board_id')
//            ->leftJoin(DB::raw('(' . $subQuerySent->toSql() . ') as sentTable'), "c1.id", "=", 'sentTable.board_id')->addBinding($subQuerySent->getBindings())
//            ->leftJoin(DB::raw('(' . $subQueryView->toSql() . ') as viewTable'), "c1.id", "=", 'viewTable.board_id')->addBinding($subQueryView->getBindings())
//            ->leftJoin(DB::raw('(' . $subQueryFailed->toSql() . ') as failTable'), "c1.id", "=", 'failTable.board_id')->addBinding($subQueryFailed->getBindings())
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
//                    ->orWhere("c1.campaign_type", 'LIKE', "%{$search}%")
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
            ->select("c1.id", "c1.name", "c1.app_group_id", "c1.status", "c1.updated_at", "c1.schedule_type", "c1.delivery_type","c1.priority")
//                DB::raw('IFNULL(totalTable.total, 0) as total'),
//                DB::raw('IFNULL(sentTable.sent, 0) as sent'),
//                DB::raw('IFNULL(viewTable.view, 0) as view'),
//                DB::raw('IFNULL(failTable.failed, 0) as failed'))
            ->get();

//        foreach ($data as $obj) {
//            $obj->targeted_users = exportUsers::exportUsers($obj->id, 'board', $obj->app_group_id, true);
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
