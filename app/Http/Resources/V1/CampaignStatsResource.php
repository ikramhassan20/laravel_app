<?php
/**
 * Created by PhpStorm.
 * User: ets-rebel
 * Date: 3/27/19
 * Time: 11:01 AM
 */

namespace App\Http\Resources\V1;

use App\AppUserActivity;
use App\AppUsers;
use App\Campaign;
use App\CampaignQueue;
use App\CampaignTracking;
use App\CampaignVariant;
use App\Components\AppStatusCodes;
use App\Components\AppStatusMessages;
use App\Components\ParseResponse;
use App\Concerns\exportUsers;
use App\ExpiredCampaignStat;
use App\Http\Resources\ResourcesSteps;
use App\Http\Resources\Contracts\ResourcesContract;
use App\Http\Resources\Contracts\ProcessResourceDataContract;
use App\Http\Resources\V1\Campaigns\TargetUsersStats;
use App\Language;
use App\LinkTrackings;
use Carbon\Carbon;
use Composer\Package\Link;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use File;

class CampaignStatsResource implements ResourcesContract, ProcessResourceDataContract
{
    use ParseResponse, ResourcesSteps;

    public function all(\Illuminate\Http\Request $request, $isListing = true)
    {
        try {
            $appGroupId = $request->user()->currentAppGroup()->id;
            $campaignCheck = Campaign::select(['id', 'app_group_id', 'is_remove_cache'])->where('id', '=', $request['campaign_id'])->where('app_group_id', '=', $appGroupId)->first();
            if (!empty($campaignCheck)) {
                if ($campaignCheck->is_remove_cache == 1) {
                    $trackings = ExpiredCampaignStat::where('campaign_id', $campaignCheck->id)->first();
                } else {
                    $trackings = CampaignTracking::with('app_user')
                        ->where('campaign_id', $request['campaign_id']);

                    if (isset($request['track_table_filter'])) {
                        $query = $request['track_table_filter'];
                        if (isset($query['start_date']) and isset($query['end_date'])) {
                            $trackings = $trackings->whereDate('sent_at', '>=', $query['start_date'])
                                ->whereDate('sent_at', '<=', $query['end_date']);
                        }
                        if (isset($query['status'])) {
                            $trackings = $trackings->where('status', $query['status']);
                        }

                        if (isset($query['variantFilter']) && $query['variantFilter'] != -1) {
                            $trackings = $trackings->where('variant_id', $query['variantFilter']);
                        }

                        if (isset($query['deviceType']) && $query['deviceType'] != -1) {
                            $trackings = $trackings->where('device_type', $query['deviceType']);
                        }

                        if (isset($query['global'])) {
                            $search = $query['global'];
                            $trackings->where(function ($query) use ($search) {
                                $query->where('track_key', 'LIKE', "%{$search}%");
                                $query->orWhere('email', 'LIKE', "%{$search}%");
                                $query->orWhere('sent_at', 'LIKE', "%{$search}%");
                                $query->orWhere('status', 'LIKE', "%{$search}%");
                                $query->orWhere('viewed_at', 'LIKE', "%{$search}%");
                                $query->orWhere('device_type', 'LIKE', "%{$search}%");
                            });
                        }
                    }
                    $totalFiltered = $trackings->count();

                    if ($isListing) {
                        $trackings = $trackings->offset(($request['page'] - 1) * $request['limit'])->limit($request['limit']);
                    }

                    if (isset($request["orderBy"])) {
                        $trackings = $trackings->orderBy($request["orderBy"], $request["ascending"] == 1 ? 'desc' : 'asc');
                    } else {
                        $trackings = $trackings->orderBy('updated_at', 'desc');
                    }
                }

                if ($isListing) {

                    $trackings = $trackings->get();

                    $meta = [
                        'pages' => ceil($totalFiltered / $request['limit']),
                        'page' => $request['page'],
                        'total' => $totalFiltered,
                    ];
                    $response = [
                        'meta' => $meta,
                        'data' => $this->getCampaignTrackingResponse($trackings, $request['campaign_id'])
                    ];
                    return $this->addResponse(
                        AppStatusCodes::HTTP_OK,
                        AppStatusMessages::SUCCESS,
                        $response['data'],
                        'data',
                        $response['meta']
                    );
                } else {

                    $company = Auth::user();
                    $files = File::cleanDirectory(storage_path('app/public/company_' . $company->id . '/export/'));

                    $fileName = 'campaign_tracking' . '_' . $request['campaign_id'] . '_app_users_data_' . Carbon::now()->timestamp;
                    $filePath = 'public/company_' . $company->id . '/export/' . $fileName . '.csv';
                    $disk = Storage::disk('local');

                    $disk->put($filePath, $this->getCSVFileHeaders($campaignCheck->is_remove_cache));

                    if ($campaignCheck->is_remove_cache == 1) {
                        $disk->append($filePath, rtrim($this->convertToString($trackings, $campaignCheck->is_remove_cache)));
                    } else {
                        $chunkSize = config('engagement.api.export.chunk_size');
                        $trackings->chunk($chunkSize, function ($rows) use ($request, $disk, $filePath, $campaignCheck) {
                            $trackRecords = $this->getCampaignTrackingResponse($rows, $request['campaign_id']);
                            $disk->append($filePath, rtrim($this->convertToString($trackRecords, $campaignCheck->is_remove_cache)));
                        });
                    }

                    return $this->addResponse(
                        AppStatusCodes::HTTP_OK,
                        AppStatusMessages::SUCCESS,
                        ['company_id' => $company->id, 'file_name' => $fileName],
                        'data'
                    );
                }
            } else {
                throw new \Exception('Invalid user.');
            }

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

    private function getCSVFileHeaders($campaignExpiredStatus)
    {
        if ($campaignExpiredStatus == 1) {
            return "Targeted Users, Total Trackings, Total Sent, Total Viewed, Total Failed, Total Android Sent, Total Android Viewed, Total Android Failed, Total IOS Sent, Total IOS Viewed, Total IOS Failed";
        } else {
            return "Email, row_id, Variant, Track key, Sent At, Status, Device Type, Viewed At, Message";
        }

    }

    private function convertToString($records, $campaignExpiredStatus)
    {
        $str = '';
        if ($campaignExpiredStatus == 1) {
            $str .= (!empty($records['targeted_users']) ? $records['targeted_users'] : 0) . ',';
            $str .= (!empty($records['total_trackings']) ? $records['total_trackings'] : 0) . ',';
            $str .= (!empty($records['total_sent']) ? $records['total_sent'] : 0) . ',';
            $str .= (!empty($records['total_viewed']) ? $records['total_viewed'] : 0) . ',';
            $str .= (!empty($records['total_failed']) ? $records['total_failed'] : 0) . ',';
            $str .= (!empty($records['total_android_sent']) ? $records['total_android_sent'] : 0) . ',';
            $str .= (!empty($records['total_android_viewed']) ? $records['total_android_viewed'] : 0) . ',';
            $str .= (!empty($records['total_android_failed']) ? $records['total_android_failed'] : 0) . ',';
            $str .= (!empty($records['total_ios_sent']) ? $records['total_ios_sent'] : 0) . ',';
            $str .= (!empty($records['total_ios_viewed']) ? $records['total_ios_viewed'] : 0) . ',';
            $str .= (!empty($records['total_ios_failed']) ? $records['total_ios_failed'] : 0) . ',';
        } else {
            foreach ($records as $record) {
                $str .= (!empty($record['email']) ? $record['email'] : 'N/A') . ',';
                $str .= (!empty($record['row_id']) ? $record['row_id'] : 'N/A') . ',';
                $str .= (!empty($record['variant']) ? $record['variant'] : 'N/A') . ',';
                $str .= (!empty($record['track_key']) ? $record['track_key'] : 'N/A') . ',';
                $str .= (!empty($record['sent_at']) ? $record['sent_at'] : 'N/A') . ',';
                $str .= (!empty($record['status']) ? $record['status'] : 'N/A') . ',';
                $str .= (!empty($record['device_type']) ? $record['device_type'] : 'N/A') . ',';
                $str .= (!empty($record['viewed_at']) ? $record['viewed_at'] : 'N/A') . ',';
                $str .= (!empty($record['message']) ? $record['message'] : 'N/A') . PHP_EOL;
            }
        }

        return $str;
    }

    private function getCampaignTrackingResponse($trackings, $campaignId)
    {
        $allVariantsOfCampaign = CampaignVariant::where('campaign_id', $campaignId)
            ->pluck('id')->toArray();

        $languages = Language::select(['id', 'name', 'code'])->get();
        $languagesArray = [];
        foreach ($languages as $language) {
            $languagesArray[$language->id] = $language->code;
        }

        $response = collect();

        foreach ($trackings as $tracking) {
            $lang = $languagesArray[$tracking->language_id];
            $response->push([
                "id" => $tracking->id,
                /*"variant_id" => $tracking->variant_id,*/
                "row_id" => $tracking->row_id,
                "variantLang" => array_search($tracking->variant_id, $allVariantsOfCampaign) . '-' . $lang,
                "variant" => 'variant-' . (array_search($tracking->variant_id, $allVariantsOfCampaign) + 1) . ': ' . $lang,
                "track_key" => $tracking->track_key,
                "email" => $tracking->app_user ? $tracking->app_user->email : '',
                "device_type" => $tracking->device_type,
                "sent_at" => $tracking->sent_at,
                "status" => $tracking->status,
                "viewed_at" => $tracking->viewed_at,
                "message" => $tracking->message
            ]);
        }

        return $response;
    }

    public function getCampaignVariants($appGroupId, $campaignId)
    {
        try {
            $exist = Campaign::where('app_group_id', $appGroupId)
                ->where('id', $campaignId)
                ->first();

            if ($exist) {

                $allVariantsOfCampaign = CampaignVariant::where('campaign_id', $campaignId)
                    ->pluck('id')->toArray();

                $arr = [];
                $itr = 1;
                foreach ($allVariantsOfCampaign as $variant) {
                    $obj = (object)[];
                    $obj->variantId = $variant;
                    $obj->label = 'variant-' . $itr;
                    $arr[] = $obj;
                    $itr++;
                }

                return $this->addResponse(
                    AppStatusCodes::HTTP_OK,
                    'success',
                    $arr,
                    'data'
                );

            }


            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                'campaign doesn\'t exist',
                'data'
            );
        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_NOT_FOUND,
                'error',
                [$exception->getMessage()],
                $exception->getMessage()
            );
        }
    }

    public function process($request, \Illuminate\Database\Eloquent\Model $model)
    {
        $queryChain = $model->where('campaign_id', '=', $request['campaign_id']);
        $totalCount = clone $queryChain;
        $totalCount = $totalCount->count();
        if ($request['sideFilters'] != null && $request['sideFilters'] != []) {
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

    public function campaignStats($id)
    {
        try {
            $campaign = Campaign::with('segments')
                ->find($id);

            $campaignQueue = CampaignQueue::where('campaign_id', $id)->first();
            $campaignTriggerStatus = true;
            if(empty($campaignQueue->status) || $campaignQueue->status == CampaignQueue::STATUS_AVAILABLE){
                $campaignTriggerStatus = false;
            }

            $composeStepVariantClass = "App\Http\Resources\V1\Campaigns\ComposeStep";

            $views = [];
            $clicks = [];
            if ($campaign->is_remove_cache == 0) {
                $views = $this->getViewsCount($campaign);
                $clicks = $this->getClicksCount($campaign);
            }
            $response = [
                "campaignTriggerStatus" => $campaignTriggerStatus,
                "campaign" => $campaign,
                "views" => $views,
                "clicks" => $clicks,
                "variants" => (new $composeStepVariantClass)->getStep($id)
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

    public function trackingStats($request, $id)
    {
        $selectedDevice = !empty($request['selectedDevice']) ? $request['selectedDevice'] : '';

        $campaign = Campaign::without(['variants', 'variants.translations', 'segments', 'actions', 'schedules'])
            ->where('id', $id)
            ->select(['id', 'app_group_id', 'is_remove_cache'])
            ->first();

        try {
            if ($campaign->is_remove_cache == 1) {
                $statsData = $this->getExpiredStatsByDevice($selectedDevice, $id);
            } else {
                $statsData = $this->fetchCampaignTrackingStats($selectedDevice, $campaign);
            }

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                AppStatusMessages::SUCCESS,
                $statsData,
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

    public function fetchCampaignTrackingStats($selectedDevice, $campaign)
    {
        $whereClause = "";
        switch ($selectedDevice) {
            case 'ios':
                $whereClause = " AND device_type='ios'";
                break;

            case 'android':
                $whereClause = " AND device_type='android'";
                break;

            default:
                $whereClause = "";
                break;
        }
        $totalQuery = DB::select("SELECT count(*) as total from campaign_tracking where campaign_id =" . $campaign->id);
        $statsData['total'] = !empty($totalQuery[0]->total) ? $totalQuery[0]->total : 0;

        $sentQuery = DB::select("SELECT count(*) as sent from campaign_tracking where campaign_id =" . $campaign->id . " AND sent <> 0" . $whereClause);
        $statsData['sent'] = !empty($sentQuery[0]->sent) ? $sentQuery[0]->sent : 0;

        $viewedQuery = DB::select("SELECT count(*) as viewed from campaign_tracking where campaign_id =" . $campaign->id . " AND viewed <> 0" . $whereClause);
        $statsData['viewed'] = !empty($viewedQuery[0]->viewed) ? $viewedQuery[0]->viewed : 0;

        $failedQuery = DB::select("SELECT count(*) as failed from campaign_tracking where campaign_id =" . $campaign->id . " AND status = 'failed'" . $whereClause);
        $statsData['failed'] = !empty($failedQuery[0]->failed) ? $failedQuery[0]->failed : 0;

        $statsData['targetedUsers'] = exportUsers::exportUsers($campaign->id, 'campaign', $campaign->app_group_id, true);

        return $statsData;
    }

    public function getExpiredStatsByDevice($selectedDevice, $campaignID)
    {
        $expiredStats = ExpiredCampaignStat::where('campaign_id', $campaignID)->first();
        $statsData = [];
        switch ($selectedDevice) {
            case 'android':
                $statsData['sent'] = $expiredStats->total_android_sent;
                $statsData['viewed'] = $expiredStats->total_android_viewed;
                $statsData['failed'] = $expiredStats->total_android_failed;
                break;

            case 'ios':
                $statsData['sent'] = $expiredStats->total_ios_sent;
                $statsData['viewed'] = $expiredStats->total_ios_viewed;
                $statsData['failed'] = $expiredStats->total_ios_failed;
                break;

            default:
                $statsData['sent'] = $expiredStats->total_sent;
                $statsData['viewed'] = $expiredStats->total_viewed;
                $statsData['failed'] = $expiredStats->total_failed;
                break;

        }
        $statsData['total'] = $expiredStats->total_trackings;
        $statsData['targetedUsers'] = $expiredStats->targeted_users;

        return $statsData;
    }

    public function getTargetUsersStats($id)
    {
        try {
            $appGroupId = Campaign::where("id", $id)->first()->app_group_id;
            $campaignTrackingRowIds = CampaignTracking::select('row_id')->where('campaign_id', $id)->get();
            $rowIds = array_map(
                function ($item) {
                    return $item['row_id'];
                },
                $campaignTrackingRowIds->toArray()
            );


            $targetObj = (object)[];
            $targetObj->targetUsersStats = (new TargetUsersStats())->getStats($rowIds);
            $targetObj->reachableUsers = count($rowIds);

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                AppStatusMessages::SUCCESS,
                $targetObj,
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

    private function getViewsCount($campaign)
    {
        $viewsCountCollection = $campaign->campaign_tracking()
            ->select([\DB::raw("COUNT(*) AS count_viewed"), \DB::raw("DATE(viewed_at) as viewed_date")])
            ->where('viewed', 1)
            ->groupBy("viewed_date")
            ->orderBy("count_viewed", "DESC")
            ->get();

        $views = collect();

        $views->push($viewsCountCollection->first());
        $views->push($viewsCountCollection->last());

        return $views;
    }

    private function getClicksCount($campaign)
    {
        $clicksCountCollection = $campaign->linkTracking()
            ->where('link_tracking.is_board', 0)
            ->select([\DB::raw("COUNT(*) AS count_clicks"), \DB::raw("DATE(created_at) as click_date")])
            ->groupBy("click_date")
            ->orderBy("count_clicks", "DESC")
            ->get();

        $clicks = collect();

        $clicks->push($clicksCountCollection->first());
        $clicks->push($clicksCountCollection->last());

        return $clicks;
    }

    public function actionTrigger(\Illuminate\Http\Request $request)
    {
        try {
            if ($request['action_table_type'] != "") {
                $actions = AppUserActivity::where('campaign_id', '=', $request['campaign_id'])->where('rec_type', '=', $request['action_table_type']);
            } else {
                $actions = AppUserActivity::where('campaign_id', '=', $request['campaign_id'])->where('rec_type', '=', 'action_trigger');
            }
            if (isset($request['action_table_filter'])) {
                $query = $request['action_table_filter'];
                if (isset($query['type'])) {
                    $actions = $actions->where('device_type', $query['type']);
                }
                if (isset($query['start_date']) and isset($query['end_date'])) {
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
            $id = $request['campaign_id'];
            $campaign = Campaign::find($id);

            if ($campaign->is_remove_cache == 1) {
                $expiredStats = ExpiredCampaignStat::where('campaign_id', $id)->first();

                $androidViewsCount = $expiredStats->total_android_viewed;
                $iosViewsCount = $expiredStats->total_ios_viewed;
            } else {
                $androidViewsCount = $this->getViewsByType($campaign, "android", $request);
                $iosViewsCount = $this->getViewsByType($campaign, "ios", $request);
            }

            $androidClicksCount = $this->getClicksByType($campaign, "android", $request);
            $iosClicksCount = $this->getClicksByType($campaign, "ios", $request);

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

    private function getViewsByType($campaign, $type, $request)
    {
        $viewsCountCollection = $campaign->campaign_tracking()
            ->where('device_type', $type)
            ->where('campaign_tracking.viewed', '<>', 0);

        if (!empty($request['start_date']) and !empty($request['end_date'])) {
            $viewsCountCollection = $viewsCountCollection->whereDate('viewed_at', '>=', $request['start_date'])
                ->whereDate('viewed_at', '<=', $request['end_date']);
        }

        if (isset($request['date'])) {
            $viewsCountCollection = $viewsCountCollection->whereDate('viewed_at', $request['date']);
        }

        $viewsCountCollection = $viewsCountCollection
            ->count();

        return $viewsCountCollection;
    }

    private function getClicksByType($campaign, $type, $request)
    {
        $viewsCountCollection = $campaign->linkTracking()
            ->where('device_type', $type)
            ->where('link_tracking.viewed', 1)
            ->where('link_tracking.is_board', 0);

        if (!empty($request['start_date']) and !empty($request['end_date'])) {
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
            $id = $request['campaign_id'];
            $type = $request->get('type');
            $campaign = Campaign::find($id);
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
                        $viewsCount = $this->getViewsByType($campaign, $device['value'], $request);
                        $options->push([
                            "y" => $viewsCount, "label" => $date
                        ]);
                    }

                    if ($type == "clicks") {
                        $clicksCount = $this->getClicksByType($campaign, $device['value'], $request);
                        $options->push([
                            "y" => $clicksCount, "label" => $date
                        ]);
                    }

                    if ($type == "click_through") {
                        $viewsCount = $this->getViewsByType($campaign, $device['value'], $request);
                        $clicksCount = $this->getClicksByType($campaign, $device['value'], $request);
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

    public function getCountriesChart(\Illuminate\Http\Request $request)
    {
        try {
            $id = $request['campaign_id'];
            $campaign = Campaign::find($id);

            $totalUsersCount = AppUsers::where('app_group_id', $campaign->app_group_id)->count();

            $countriesData = DB::select("SELECT 
                            COUNT(*)/{$totalUsersCount} * 100 as `y`, country as `name`
                        FROM
                            app_user
                        WHERE
                            app_group_id = {$campaign->app_group_id}
                        GROUP BY country");

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                AppStatusMessages::SUCCESS,
                $countriesData,
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

    public function getActivityChart(\Illuminate\Http\Request $request)
    {
        try {
            $id = $request['campaign_id'];
            $campaign = Campaign::find($id);

            $activityData = [];
            $recipients = 0;

            if ($campaign->is_remove_cache == 0) {
                $recipients = CampaignTracking::where('campaign_id', $campaign->id)->count();
                $opens = CampaignTracking::where('campaign_id', $campaign->id)->where('viewed', '<>', 0)->count();
                $uniqueOpens = CampaignTracking::where('campaign_id', $campaign->id)->where('viewed', '<>', 0)->distinct('row_id')->count('row_id');
            } else {
                $recipients = (ExpiredCampaignStat::select('total_trackings')->where('campaign_id', $campaign->id)->first())->total_trackings;
                $opens = (ExpiredCampaignStat::select('total_viewed')->where('campaign_id', $campaign->id)->first())->total_viewed;
                $uniqueOpens = (ExpiredCampaignStat::select('total_unique_viewed')->where('campaign_id', $campaign->id)->first())->total_unique_viewed;
            }

            $unopens = !(empty($opens) && !empty($recipients)) ? $recipients - $opens : 0;

            $clicks = LinkTrackings::where('rec_id', $campaign->id)->where('rec_type', 'email')->count();

            $bounces = AppUsers::where('app_group_id', $campaign->app_group_id)->where('is_bounced', 1)->count();

            array_push($activityData, ['y' => $bounces, 'label' => 'Bounces']);
            array_push($activityData, ['y' => $clicks, 'label' => 'Clicks']);
            array_push($activityData, ['y' => $unopens, 'label' => 'Unopens']);
            array_push($activityData, ['y' => $opens, 'label' => 'Opens']);
            array_push($activityData, ['y' => $recipients, 'label' => 'Recipients']);

            $uniqueClicks = LinkTrackings::where('rec_id', $campaign->id)->where('rec_type', 'email')->distinct('row_id')->count('row_id');

            $stats = [];
            $stats['opened'] = $opens;
            $stats['openedPercentage'] = (!empty($recipients)) ? round($opens / $recipients * 100, 2) : 0;
            $stats['notOpened'] = round($unopens, 2);
            $stats['bounced'] = $bounces;
            $stats['bouncedPercentage'] = (!empty($recipients)) ? round($bounces / $recipients * 100, 2) : 0;
            $stats['uniqueOpens'] = $uniqueOpens;
            $stats['uniqueClicks'] = $uniqueClicks;
            $stats['clicksPercentage'] = (!empty($recipients)) ? round($clicks / $recipients * 100, 2) : 0;
            $stats['recipients'] = $recipients;

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                AppStatusMessages::SUCCESS,
                [
                    'activityChart' => $activityData,
                    'activityStats' => $stats
                ],
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

    public function getLinkActivityStats(\Illuminate\Http\Request $request)
    {
        try {
            $id = $request['campaign_id'];
            $campaign = Campaign::find($id);
            $linkActivityData = DB::select("SELECT count(*) as total, actual_url FROM link_tracking where rec_id=" . $campaign->id . " group by actual_url");

            $lastTenOpenedData = [];
            if ($campaign->is_remove_cache == 0) {
                $query = DB::select("SELECT row_id FROM campaign_tracking where campaign_id=" . $campaign->id . " AND viewed > 0 ORDER BY viewed_at DESC limit 10");
                $lastTenRowIDs = [];
                foreach ($query as $item) {
                    array_push($lastTenRowIDs, $item->row_id);
                }

            } else {
                $lastTenRowIDs = (ExpiredCampaignStat::select('last_ten_row_ids')->where('campaign_id', $campaign->id)->first())->last_ten_row_ids;
                $lastTenRowIDs = !empty($lastTenRowIDs) ? json_decode($lastTenRowIDs) : [];
            }

            $lastTenOpenedData = AppUsers::select(['user_id', 'row_id', 'firstname', 'lastname', 'username', 'email', 'status'])
                ->whereIn('row_id', $lastTenRowIDs)
                ->get();

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                AppStatusMessages::SUCCESS,
                [
                    'link_activity' => $linkActivityData,
                    'last_ten_opened' => $lastTenOpenedData
                ],
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

    public function exportActivityStats(\Illuminate\Http\Request $request)
    {
        $id = $request->get('campaign_id');
        $type = $request->get('type');
        $response = [];
        if (!empty($type)) {

            switch ($type) {

                case 'last-ten-opened':
                    $response = $this->exportLastTenOpenedData($id);
                    break;

                case 'link-activity':
                    $response = $this->exportLinkActivityData($id);
                    break;

                default:
                    $response = [];
                    break;

            }

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                AppStatusMessages::SUCCESS,
                $response,
                'data'
            );

        }
    }

    public function exportLastTenOpenedData($id)
    {

        $campaign = Campaign::find($id);

        $lastTenOpenedData = [];
        if ($campaign->is_remove_cache == 0) {
            $query = DB::select("SELECT * FROM campaign_tracking where campaign_id=" . $campaign->id . " AND viewed > 0 ORDER BY viewed_at DESC limit 10");
            $lastTenRowIDs = [];
            foreach ($query as $item) {
                array_push($lastTenRowIDs, $item->row_id);
            }

        } else {
            $lastTenRowIDs = (ExpiredCampaignStat::select('last_ten_row_ids')->where('campaign_id', $campaign->id)->first())->last_ten_row_ids;
            $lastTenRowIDs = !empty($lastTenRowIDs) ? json_decode($lastTenRowIDs) : '';
        }

        $lastTenOpenedData = AppUsers::select(['row_id', 'app_group_id', 'company_id', 'user_id', 'app_id', 'username', 'firstname', 'lastname', 'email', 'timezone', 'latitude', 'longitude', 'country', 'last_login', 'enable_notification', 'email_notification', 'status'])
            ->whereIn('row_id', $lastTenRowIDs)
            ->get();

        $company = Auth::user();
        $files = File::cleanDirectory(storage_path('app/public/company_' . $company->id . '/export/'));

        $fileName = 'campaign_' . $id . '_last_10_opened_app_users_data_' . Carbon::now()->timestamp;
        $filePath = 'public/company_' . $company->id . '/export/' . $fileName . '.csv';
        $disk = Storage::disk('local');

        $headers = 'row_id, app_group_id, company_id, user_id, app_id, username, firstname, lastname, email, timezone, latitude, longitude, country, last_login, enable_notification, email_notification, status';

        $disk->put($filePath, $headers);

        foreach ($lastTenOpenedData as $record) {

            $str = '';
            $str .= (!empty($record['row_id']) ? $record['row_id'] : 'N/A') . ',';
            $str .= (!empty($record['app_group_id']) ? $record['app_group_id'] : 'N/A') . ',';
            $str .= (!empty($record['company_id']) ? $record['company_id'] : 'N/A') . ',';
            $str .= (!empty($record['user_id']) ? $record['user_id'] : 'N/A') . ',';
            $str .= (!empty($record['app_id']) ? $record['app_id'] : 'N/A') . ',';
            $str .= (!empty($record['username']) ? $record['username'] : 'N/A') . ',';
            $str .= (!empty($record['firstname']) ? $record['firstname'] : 'N/A') . ',';
            $str .= (!empty($record['lastname']) ? $record['lastname'] : 'N/A') . ',';
            $str .= (!empty($record['email']) ? $record['email'] : 'N/A') . ',';
            $str .= (!empty($record['timezone']) ? $record['timezone'] : 'N/A') . ',';
            $str .= (!empty($record['latitude']) ? $record['latitude'] : 'N/A') . ',';
            $str .= (!empty($record['longitude']) ? $record['longitude'] : 'N/A') . ',';
            $str .= (!empty($record['country']) ? $record['country'] : 'N/A') . ',';
            $str .= (!empty($record['last_login']) ? $record['last_login'] : 'N/A') . ',';
            $str .= (!empty($record['enable_notification']) ? $record['enable_notification'] : 'N/A') . ',';
            $str .= (!empty($record['email_notification']) ? $record['email_notification'] : 'N/A') . ',';
            $str .= (!empty($record['status']) ? $record['status'] : 'N/A') . ',';

            $disk->append($filePath, rtrim($str));

        }
        return ['company_id' => $company->id, 'file_name' => $fileName];
    }

    public function exportLinkActivityData($id)
    {
        $campaign = Campaign::find($id);

        $linkActivityData = DB::select("SELECT count(*) as total, actual_url FROM link_tracking where rec_id=" . $campaign->id . " group by actual_url");

        $company = Auth::user();
        $files = File::cleanDirectory(storage_path('app/public/company_' . $company->id . '/export/'));

        $fileName = 'campaign_' . $id . '_link_activity_data_' . Carbon::now()->timestamp;
        $filePath = 'public/company_' . $company->id . '/export/' . $fileName . '.csv';
        $disk = Storage::disk('local');

        $headers = 'URL, Total';

        $disk->put($filePath, $headers);

        foreach ($linkActivityData as $linkActivityDatum) {
            $str = '';
            $str .= (!empty($linkActivityDatum->actual_url) ? $linkActivityDatum->actual_url : 'N/A') . ',';
            $str .= (!empty($linkActivityDatum->total) ? $linkActivityDatum->total : 'N/A') . ',';
            $disk->append($filePath, rtrim($str));
        }

        return ['company_id' => $company->id, 'file_name' => $fileName];
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
            new \DateTime(Carbon::now()->addDays(-6)->format('Y-m-d')),
            new \DateInterval('P1D'),
            new \DateTime(Carbon::now()->format('Y-m-d'))
        );
        $dates = [];
        foreach ($period as $key => $value) {
            array_push($dates, $value->format('Y-m-d'));
        }

        // adding current date as well
        array_push($dates, Carbon::now()->format('Y-m-d'));

        return $dates;
    }
}