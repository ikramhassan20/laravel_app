<?php
/**
 * Created by PhpStorm.
 * User: ets-rebel
 * Date: 1/23/19
 * Time: 4:38 PM
 */

namespace App\Components;

use App\Concerns\exportUsers;
use App\Http\FcmNotification\SendNotification;
use App\NewsFeed;
use App\Segment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RenderNewFeedPaginateResponse
{
    use exportUsers;

    public function renderpaginate(Request $request)
    {
        $queryChain = DB::table("news_feed as nf1")
            ->leftJoin("segment as s1", "nf1.segment_id", "=", "s1.id")
            ->leftJoin("location as l1", "nf1.location_id", "=", "l1.id")
            ->join("templates as t1", "nf1.news_feed_template_id", "=", "t1.id")
            ->where("nf1.app_group_id", $request->user()->currentAppGroup()->id)
            ->where("nf1.deleted_at", null);


        $totalCount = clone $queryChain;
        $totalCount = $totalCount->count();

        if ($request['sideFilters'] != null && $request['sideFilters'] != [] && $request['sideFilters'] != "") {
            if ($request['sideFilters']['parent'] == "nf1.tags") {
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
                $query->where("nf1.name", 'LIKE', "%{$search}%")
                    ->orWhere("l1.name", 'LIKE', "%{$search}%")
                    ->orWhere("s1.name", 'LIKE', "%{$search}%")
                    ->orWhere("nf1.start_time", 'LIKE', "%{$search}%")
                    ->orWhere("nf1.end_time", 'LIKE', "%{$search}%")
                    ->orWhere("nf1.status", 'LIKE', "%{$search}%");
            });
        }

        $totalFiltered = clone $queryChain;
        $totalFiltered = $totalFiltered->count();

        isset($request["orderBy"]) ? $queryChain->orderBy(str_replace("-", ".", $request["orderBy"]), $request["ascending"] == 1 ? 'desc' : 'asc') : $queryChain->orderBy('nf1.updated_at', 'desc');
        $data = $queryChain->offset(($request['page'] - 1) * $request['limit'])
            ->limit($request['limit'])
            ->select(
                DB::raw('IFNULL(nf1.id, "N/A") as id'),
                DB::raw('IFNULL(nf1.name, "N/A") as "nf1-name"'),
                DB::raw('IFNULL(l1.name, "N/A") as "l1-name"'),
                DB::raw('IFNULL(s1.name, "N/A") as "s1-name"'),
                DB::raw('IFNULL(nf1.start_time, "N/A") as "nf1-start_time"'),
                DB::raw('IFNULL(nf1.end_time, "N/A") as "nf1-end_time"'),
                DB::raw('IFNULL(nf1.status, "N/A") as "nf1-status"'),
                'nf1.segment_id',
                'nf1.app_group_id'
            )
            ->get();

        $meta = [
            'pages' => ceil($totalFiltered / $request['limit']),
            'page' => $request['page'],
            'total' => $totalFiltered,
        ];


        foreach ($data as $row) {

            $row->stats = (object)[];
            $row->stats->targetedUsers = $row->segment_id == null ? 0 : exportUsers::exportUsers($row->segment_id, 'segment', $row->app_group_id, true);
            $row->stats->segment = $row->segment_id == null ? 'N/A' : Segment::find($row->segment_id)->name;

            $row->stats->click = DB::table("link_tracking")
                ->where("rec_type", "newsfeed")
                ->where("rec_id", $row->id)
                ->count();

            $row->stats->newsFeedImpression = DB::table("news_feed_impression")
                ->where("news_feed_id", $row->id)
                ->count();

            $row->stats->clickThrough = $row->stats->newsFeedImpression > 0 ? (($row->stats->click / $row->stats->newsFeedImpression) * 100) : "N/A";

            unset($row->segment_id);
            unset($row->app_group_id);
        }

        return [
            'meta' => $meta,
            'data' => $data
        ];

    }

}