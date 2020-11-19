<?php

namespace App\Http\Resources\V1;

use App\AppUserActivity;
use App\Campaign;
use App\CampaignTracking;
use App\CampaignVariant;
use App\Components\AppStatusCodes;
use App\Components\AppStatusMessages;
use App\Components\ParseResponse;
use App\Http\Resources\ResourcesSteps;
use App\Http\Resources\Contracts\ResourcesContract;
use App\Http\Resources\Contracts\ProcessResourceDataContract;
use App\LinkTrackings;
use App\NewsFeed;
use App\NewsFeedImpression;
use App\Segment;
use Aws\Swf\SwfClient;
use Carbon\Carbon;

class NewsFeedStatsResource implements ResourcesContract, ProcessResourceDataContract
{
    use ParseResponse, ResourcesSteps;

    public function all(\Illuminate\Http\Request $request)
    {
    }

    public function process($request, \Illuminate\Database\Eloquent\Model $model)
    {

    }

    public function create(\Illuminate\Http\Request $request)
    {
    }

    public function show(\Illuminate\Database\Eloquent\Model $model)
    {
    }

    public function update(\Illuminate\Http\Request $request, \Illuminate\Database\Eloquent\Model $model)
    {
    }

    public function remove(\Illuminate\Database\Eloquent\Model $model)
    {
    }

    public function newsFeedStats($id)
    {
        try {
            $newsfeed = NewsFeed::find($id);
            $newsfeed->segment = Segment::where('id', $newsfeed->segment_id)
                ->first()->name;

            $composeStepVariantClass = "App\Http\Resources\V1\NewsFeeds\ComposeStep";

            $response = [
                "newsfeed" => $newsfeed,
                "views" => $this->getViewsCount($id),
                "clicks" => $this->getClicksCount($id),
                "lang" => (new $composeStepVariantClass)->getNewsFeedLangAndTemplates($id)
            ];

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                AppStatusMessages::SUCCESS,
                $response,
                'data',
                []
            );
        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_NOT_FOUND,
                'error',
                ['Unable to load data'],
                $exception->getMessage()
            );
        }
    }

    private function getViewsCount($id)
    {
        $viewsCountCollection = NewsFeedImpression::select([\DB::raw("COUNT(*) AS count_viewed"), \DB::raw("DATE(created_date) as created_at")])
            ->where('viewed', 1)
            ->groupBy(\DB::raw("DATE(created_date)"))
            ->orderBy("count_viewed", "DESC")
            ->where('news_feed_id', '=', $id)->get();
        $views = collect();
        $views->push($viewsCountCollection->first());
        $views->push($viewsCountCollection->last());
        return $views;
    }

    private function getClicksCount($id)
    {
        $viewsCountCollection = LinkTrackings::where('rec_type', 'newsfeed')
            ->select([\DB::raw("COUNT(*) AS count_viewed"), \DB::raw("DATE(created_date) as created_at")])
            ->where('viewed', 1)
            ->groupBy(\DB::raw("DATE(created_date)"))
            ->orderBy("count_viewed", "DESC")
            ->where('rec_id', '=', $id)->get();
        $clicks = collect();
        $clicks->push($viewsCountCollection->first());
        $clicks->push($viewsCountCollection->last());
        return $clicks;
    }

    public function actionTrigger(\Illuminate\Http\Request $request)
    {
        try {
            $actions = AppUserActivity::where('campaign_id', $request['campaign_id'])->where('rec_type', 'action_trigger');

            if (isset($request['action_table_filter'])) {
                $query = $request['action_table_filter'];
                if ($query['type']) {
                    $actions = $actions->where('device_type', $query['type']);
                }
                if (isset($query['start_date']) AND isset($query['end_date'])) {
                    $actions = $actions->whereDate('created_at', '>=', $query['start_date'])
                        ->whereDate('created_at', '<=', $query['end_date']);
                }

                if (isset($query['global'])) {
                    $filter = $query['global'];
                    $actions = $actions->where('event_id', $filter)
                        ->orWhere('event_id', $filter)
                        ->orWhere('event_value', $filter)
                        ->orWhere('track_key', $filter)
                        ->orWhere('device_type', $filter)
                        ->orWhere('campaign_code', $filter);
                }
            }

            $totalFiltered = $actions->count();

            $actions = $actions->offset(($request['page'] - 1) * $request['limit'])->limit($request['limit']);

            if (isset($request["orderBy"])) {
                $actions = $actions->orderBy($request["orderBy"], $request["ascending"] == 1 ? 'desc' : 'asc');
            } else {
                $actions = $actions->orderBy('updated_at', 'desc');
            }

            $actions = $actions->get();

            $meta = [
                'pages' => ceil($totalFiltered / $request['limit']),
                'page' => $request['page'],
                'total' => $totalFiltered,
            ];
            $response = [
                'meta' => $meta,
                'data' => $actions
            ];
            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                AppStatusMessages::SUCCESS,
                $response['data'],
                'data',
                $response['meta']
            );
        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_NOT_FOUND,
                'error',
                [$exception->getMessage()],
//                ['Unable to load action stats data'],
                $exception->getMessage()
            );
        }
    }

    public function getViewsClicksCount(\Illuminate\Http\Request $request)
    {
        try {
            $user = $request->user();
            $group = $user->currentAppGroup();
            $id = $request['newsfeed_id'];
            $newsfeed = NewsFeed::find($id);
            if ($newsfeed['app_group_id'] != $group->id) {
                return $this->addResponse(
                    AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                    'error',
                    ['Invalid user.'],
                    'error'
                );
            }
            $androidViewsCount = $this->getViewsByType($newsfeed, "android", $request);
            $iosViewsCount = $this->getViewsByType($newsfeed, "ios", $request);

            $androidClicksCount = $this->getClicksByType($newsfeed, "android", $request);
            $iosClicksCount = $this->getClicksByType($newsfeed, "ios", $request);

            $androidPercentage = 0;
            if ($androidViewsCount && $androidClicksCount) {
                $androidPercentage = ($androidClicksCount / $androidViewsCount) * 100;
            }

            $iosPercentage = 0;
            if ($iosViewsCount && $iosClicksCount) {
                $iosPercentage = ($iosClicksCount / $iosViewsCount) * 100;
            }

            $response = [
                "ios" => [
                    "views" => $iosViewsCount,
                    "clicks" => $iosClicksCount,
                    "percentage" => round($iosPercentage)
                ],
                "android" => [
                    "views" => $androidViewsCount,
                    "clicks" => $androidClicksCount,
                    "percentage" => round($androidPercentage)
                ]
            ];

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                AppStatusMessages::SUCCESS,
                $response,
                'data',
                []
            );
        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_NOT_FOUND,
                'error',
                [$exception->getMessage()],
//                ['Unable to load data'],
                $exception->getMessage()
            );
        }
    }

    private function getViewsByType($newsfeed, $type, $request)
    {
        $viewsCountCollection = NewsFeedImpression::where('news_feed_id', '=', $request['newsfeed_id'])
            ->where('platform', '=', $type);
        if (!empty($request['start_date']) AND !empty($request['end_date'])) {
            $viewsCountCollection = $viewsCountCollection->whereDate('created_at', '>=', $request['start_date'])
                ->whereDate('created_at', '<=', $request['end_date']);
        }

        if (isset($request['date'])) {
            $viewsCountCollection = $viewsCountCollection->whereDate('created_at', $request['date']);
        }
        $viewsCountCollection = $viewsCountCollection
            ->count();
        return $viewsCountCollection;
    }

    private function getClicksByType($newsfeed, $type, $request)
    {
        $viewsCountCollection = LinkTrackings::where('rec_type', 'newsfeed')
            ->where('rec_id', '=', $request['newsfeed_id'])
            ->where('device_type', '=', $type);
        if (!empty($request['start_date']) AND !empty($request['end_date'])) {
            $viewsCountCollection = $viewsCountCollection->whereDate('created_at', '>=', $request['start_date'])
                ->whereDate('created_at', '<=', $request['end_date']);
        }

        if (isset($request['date'])) {
            $viewsCountCollection = $viewsCountCollection->whereDate('created_at', $request['date']);
        }
        $viewsCountCollection = $viewsCountCollection
            ->count();
        return $viewsCountCollection;
    }

    public function getViewsClicksChart(\Illuminate\Http\Request $request)
    {
        try {
            $id = $request['newsfeed_id'];
            $type = $request->get('type');
            $newsfeed = NewsFeed::find($id);
            $devices = $this->getDevices();

            $response = collect();

            foreach ($devices as $device) {
                $dates = $this->getLastSevenDays();
                $data = [
                    "type" => "column",
                    "showInLegend" => false,
                    "name" => $device['label'],
                    "color" => $device['color'],
                    "dataPoints" => []
                ];

                $options = collect();

                foreach ($dates as $date) {
                    $request['date'] = $date;

                    if ($type == "views") {
                        $viewsCount = $this->getViewsByType($newsfeed, $device['value'], $request);
                        $options->push([
                            "y" => $viewsCount, "label" => $date
                        ]);
                    }

                    if ($type == "clicks") {
                        $clicksCount = $this->getClicksByType($newsfeed, $device['value'], $request);
                        $options->push([
                            "y" => $clicksCount, "label" => $date
                        ]);
                    }

                    if ($type == "click_through") {
                        $viewsCount = $this->getViewsByType($newsfeed, $device['value'], $request);
                        $clicksCount = $this->getClicksByType($newsfeed, $device['value'], $request);
                        $percentage = 0;
                        if ($viewsCount && $clicksCount) {
                            $percentage = ($clicksCount / $viewsCount) * 100;
                        }
                        $options->push([
                            "y" => $percentage, "label" => $date
                        ]);
                    }
                }
                $data['dataPoints'] = $options;

                $response->push($data);
            }

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                AppStatusMessages::SUCCESS,
                $response->toArray(),
                'data',
                []
            );
        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_NOT_FOUND,
                'error',
                [$exception->getMessage()],
//                ['Unable to load data'],
                $exception->getMessage()
            );
        }
    }

    public function getDevices()
    {
        $response = [];

        array_push($response, [
            "color" => "#7cb5ec",
            "label" => "android",
            "value" => "android"
        ]);
        array_push($response, [
            "color" => "#434348",
            "label" => "ios",
            "value" => "ios"
        ]);

        return $response;
    }

    private function getLastSevenDays()
    {
        $period = new \DatePeriod(
            new \DateTime(Carbon::now()->subDays(7)->addDay(1)->format('Y-m-d')),
            new \DateInterval('P1D'),
            new \DateTime(Carbon::now()->addDay(1)->format('Y-m-d'))
        );
        $dates = [];
        foreach ($period as $key => $value) {
            array_push($dates, $value->format('Y-m-d'));
        }
        return $dates;
    }
}