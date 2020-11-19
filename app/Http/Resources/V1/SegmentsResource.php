<?php

namespace App\Http\Resources\V1;

use App\AppGroup;
use App\Components\AppStatusCodes;
use App\Components\ParseResponse;
use App\Components\RenderPaginatedResponse;
use App\Concerns\exportUsers;
use App\Concerns\tagsCount;
use App\Http\Resources\Contracts\ProcessResourceDataContract;
use App\Http\Resources\Contracts\ResourcesContract;
use App\Http\Resources\ResourcesSteps;
use App\Segment;
use App\Cache\AppGroupSegmentCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class SegmentsResource implements ResourcesContract, ProcessResourceDataContract
{
    use ParseResponse, ResourcesSteps, RenderPaginatedResponse, tagsCount, exportUsers;

    /**
     * Get list of all segments.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function all(Request $request)
    {
        try {
            $companyId = $request->user()->id;
            $id = Auth::user()->id;
            if ($companyId != $id) {
                throw new \Exception('Invalid User');
            }
            $response = $this->segmentPaginationResponse($request);

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $response['data'],
                'data',
                $response['meta']
            );
        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_NOT_FOUND,
                'error',
                [$exception->getMessage()],
                'error'
            );
        }
    }

    /**
     * Create a new segment.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        try {
            $companyId = $request->user()->id;
            $id = Auth::user()->id;
            if ($companyId != $id) {
                throw new \Exception('Invalid User');
            }
            $segment = $this->process($request, new Segment());

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $segment,
                'data'
            );
        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                'error',
                ['Unable to create segment'],
                'error'
            );
        }
    }

    /**
     * Update data for a segment.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Database\Eloquent\Model $model
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, \Illuminate\Database\Eloquent\Model $model)
    {
        try {
            $companyId = $request->user()->id;
            $id = Auth::user()->id;
            if ($companyId != $id) {
                throw new \Exception('Invalid User');
            }
            $segment = $this->process($request, $model);

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $segment,
                'data'
            );
        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                'error',
                ['Unable to update segment'],
                'error'
            );
        }
    }

    /**
     * Process segment data.
     *
     * @param array|\Illuminate\Http\Request $request
     * @param \Illuminate\Database\Eloquent\Model $model
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function process($request, \Illuminate\Database\Eloquent\Model $model)
    {
        $companyId = $request->user()->id;
        $id = Auth::user()->id;
        if ($companyId != $id) {
            throw new \Exception('Invalid User');
        }

        $data = $this->parseResponse($request);
        $user = $request->user();
        $segmentId = [];

        if (!isset($model->id)) {
            $app_group = $user->currentAppGroup();

            $model->created_by = $user->id;
            $model->app_group_id = $app_group->id;
        } else {
            $segmentId[] = $model->id;
            //event(new \App\Events\AddSegmentCacheEvent($model));
        }

        $duplicateRecord = Segment::where("app_group_id", $user->currentAppGroup()->id)
            ->where("name", $data['name'])
            ->whereNotIn("id", $segmentId)
            ->first();

        if ($duplicateRecord) {
            return [
                "dialogueOpen" => "true",
                "status" => false,
                "message" => "Segment Name Already Exist"
            ];
        }

        $model->name = $data['name'];
        $model->tags = $data['tags'] != null ? $data['tags'] : '';
        $model->criteria = base64_decode($data['criteria']);
        $model->attribute_fields = implode(",", $data['attributeType']['user']);
        $model->action_fields = implode(",", $data['attributeType']['action']);
        $model->conversion_fields = implode(",", $data['attributeType']['conversion']);

        if (isset($data['rules'])) {
            $model->rules = \GuzzleHttp\json_encode($data['rules']);
        }

        $model->updated_by = $user->id;

        $model->save();

        event(new \App\Events\AddSegmentCacheEvent($model));

//        $segment_cache = new AppGroupSegmentCache();
//        $segment_cache->saveAppGroupSegmentCache($model);
//
//        $status = $segment_cache->saveAppGroupSegmentRowsCache($model, true);
//
//        if ($status === false) {
//            $segment_cache->saveAppGroupSegmentRowsCache($model);
//        }

        return $model->fresh();
    }

    /**
     * Remove segment cache data.
     *
     * @param array|\Illuminate\Http\Request $request
     * @param \Illuminate\Database\Eloquent\Model $model
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function removeSegmentCache(\Illuminate\Database\Eloquent\Model $model)
    {
        event(new \App\Events\RemoveSegmentCacheEvent($model));

        return $model->fresh();
    }

    public function get($segmentId)
    {
        try {
            $segmentExist = Segment::where("id", $segmentId)
                ->where("app_group_id", \Request::user()->currentAppGroup()->id)
                ->first();

            if (!$segmentExist) {

                return $this->addResponse(
                    AppStatusCodes::HTTP_OK,
                    'success',
                    [
                        "status" => false,
                        "message" => "segment not found"
                    ],
                    'data'
                );
            }

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $segmentExist,
                'data'
            );
        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                'error',
                ['Unable to update segment'],
                'error'
            );
        }
    }

    public function segmentPaginationResponse($request)
    {
        $companyId = $request->user()->id;
        $id = Auth::user()->id;
        if ($companyId != $id) {
            throw new \Exception('Invalid User');
        }
        $subQueryCampaign = DB::table("campaign_segment")
            ->groupBy("campaign_segment.segment_id")
            ->select("campaign_segment.segment_id",
                DB::raw('count(campaign_segment.segment_id) as total_campaigns'));

        $subQueryNewsFeed = DB::table("news_feed")
            ->groupBy("news_feed.segment_id")
            ->select("news_feed.segment_id", DB::raw('count(news_feed.segment_id) as total_news_feeds'));

        $subQueryBoard = DB::table("board_segment")
            ->groupBy("board_segment.segment_id")
            ->select("board_segment.segment_id",
                DB::raw('count(board_segment.segment_id) as total_boards'));

        $queryChain = DB::table("segment as s1")
            //->join("app_group", "s1.app_group_id", "=", "app_group.id")
            ->leftJoin(DB::raw('(' . $subQueryCampaign->toSql() . ') as x1'), "s1.id", "=", 'x1.segment_id')
            ->leftJoin(DB::raw('(' . $subQueryNewsFeed->toSql() . ') as x2'), "s1.id", "=", "x2.segment_id")
            ->leftJoin(DB::raw('(' . $subQueryBoard->toSql() . ') as x3'), "s1.id", "=", "x3.segment_id")
            //->where("app_group.company_id", "=", $request->user()->id);
            //->where("is_active", 1)
            ->where("app_group_id", "=", $request->user()->currentAppGroup()->id);

        $totalCount = clone $queryChain;
        $totalCount = $totalCount->count();

        if ($request['sideFilters'] != null && $request['sideFilters'] != []) {
            if ($request['sideFilters']['parent'] == "tags") {
                $value = $request['sideFilters']['value'];
                $column = $request['sideFilters']['parent'];
                $queryChain->whereRaw("FIND_IN_SET('$value', BINARY $column) > 0");
            } else {
                $queryChain->where($request['sideFilters']['parent'], strtolower($request['sideFilters']['value']) == 'active' ? 1 : 0);
            }
        }
        if ($request['query'] != null) {
            $search = $request['query'];
            $columns = $request['columns'];
            $queryChain->where(function ($query) use ($search, $columns) {
                $query->where("s1.name", 'LIKE', "%{$search}%")
                    ->orWhere("total_campaigns", 'LIKE', "%{$search}%")
                    ->orWhere("total_news_feeds", 'LIKE', "%{$search}%")
                    ->orWhere("total_boards", 'LIKE', "%{$search}%")
                    ->orWhere("s1.updated_at", 'LIKE', "%{$search}%");
            });
        }

        $totalFiltered = clone $queryChain;
        $totalFiltered = $totalFiltered->count();

        isset($request["orderBy"]) ? $queryChain->orderBy($request["orderBy"], $request["ascending"] == 1 ? 'desc' : 'asc') : $queryChain->orderBy('updated_at', 'desc');
        $data = $queryChain->offset(($request['page'] - 1) * $request['limit'])
            ->limit($request['limit'])
            ->select("s1.*", DB::raw('IFNULL(x1.total_campaigns, 0) as total_campaigns'), DB::raw('IFNULL(x2.total_news_feeds, 0) as total_news_feeds'), DB::raw('IFNULL(x3.total_boards, 0) as total_boards'))
            ->get();

        foreach ($data as $obj) {
            $obj->targeted_users = exportUsers::exportUsers($obj->id, "segment", $obj->app_group_id, true);
        }

        $meta = [
            'pages' => ceil($totalFiltered / $request['limit']),
            'page' => $request['page'],
            //'total' => $totalCount,
            'total' => $totalFiltered,
        ];

        return [
            'meta' => $meta,
            'data' => $data
        ];
    }

    public function segmentTagsCount($appGroupId)
    {
        try {
            $segmentSideFilters = [];
            $obj = (object)[];
            $obj->column = "tags";
            $obj->children = [];
            $tags = tagsCount::findTagsCount($appGroupId, 'segment');

            foreach ($tags as $tag) {
                $tagObj = (object)[];
                $tagObj->parent = "tags";
                $tagObj->value = $tag->tags;
                $obj->children[] = $tagObj;
            }

            $segmentSideFilters[] = clone $obj;


            $status = [

                "column" => "Status",
                "children" => [
                    [
                        "parent" => "is_active",
                        "value" => "active"
                    ],
                    [
                        "parent" => "is_active",
                        "value" => "inactive"
                    ],
                ]
            ];


            $segmentSideFilters[] = $status;

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $segmentSideFilters,
                'data'
            );

        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                'error',
                ['Unable to get segment filters'],
                'error'
            );
        }
    }

    public function exportUsers($segmentId, $appGroupId)
    {
        try {
            $users = exportUsers::exportUsers($segmentId, 'segment', $appGroupId);

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $users,
                'data'
            );
        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                'error',
                [$exception->getMessage()],
                'error'
            );
        }
    }

    public function changeStatus($segmentId, $status)
    {
        try {

            if ($status == 0) {
                $segmentInfo = DB::table('segment')
                    ->leftJoin('campaign_segment', 'segment.id', '=', 'campaign_segment.segment_id')
                    ->leftJoin('news_feed', 'segment.id', '=', 'news_feed.segment_id')
                    ->where('segment.id', $segmentId)
                    ->select('campaign_segment.segment_id as cSid', 'news_feed.segment_id as nSid')
                    ->first();

                if ($segmentInfo->cSid == null && $segmentInfo->nSid == null) {

                    $segment = Segment::find($segmentId);
                    $segment->is_active = $status;
                    $segment->save();

                    // delete segment cache for this campaign...
                    // delete from SegmentCache and from SegmentRowsCache both
                    event(new \App\Events\RemoveSegmentCacheEvent($segment));

                    return $this->addResponse(
                        AppStatusCodes::HTTP_OK,
                        'success',
                        [
                            "status" => true,
                            "message" => "Segment inactivated successfully."
                        ],
                        'data'
                    );
                }

                return $this->addResponse(
                    AppStatusCodes::HTTP_OK,
                    'error',
                    [
                        "status" => false,
                        "message" => "Segment cannot be inactivate."
                    ],
                    'data'
                );

            } else {

                $segment = Segment::find($segmentId);
                $segment->is_active = $status;
                $segment->save();

                // re-build again segment cache
                event(new \App\Events\AddSegmentCacheEvent($segment));

                return $this->addResponse(
                    AppStatusCodes::HTTP_OK,
                    'success',
                    [
                        "status" => true,
                        "message" => "Segment activated successfully."
                    ],
                    'data'
                );
            }

        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                'error',
                [$exception->getMessage()],
                'error'
            );
        }
    }
}


