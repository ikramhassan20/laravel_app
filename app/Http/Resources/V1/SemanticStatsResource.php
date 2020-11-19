<?php

namespace App\Http\Resources\V1;

use App\AppUserActivity;
use App\AppUsers;
use App\Board;
use App\BoardQueue;
use App\BoardTracking;
use App\Campaign;
use App\CampaignTracking;
use App\CampaignVariant;
use App\Components\AppStatusCodes;
use App\Components\AppStatusMessages;
use App\Concerns\exportUsers;
use App\ExpiredBoardStat;
use App\Http\Controllers\NotificationController;
use App\Http\Resources\Contracts\ProcessResourceDataContract;
use App\Http\Resources\Contracts\ResourcesContract;
use App\Http\Resources\ResourcesSteps;
use App\Components\ParseResponse;
use App\Language;
use App\LinkTrackings;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;
use File;

class SemanticStatsResource implements ResourcesContract, ProcessResourceDataContract
{
    use ParseResponse, ResourcesSteps;

    public function all(\Illuminate\Http\Request $request, $isListing = true)
    {
        try {
            $trackings = BoardTracking::leftjoin('board_variant_step', 'board_variant_step.id', '=', 'board_tracking.variant_step_id')
                ->leftjoin('board_variant', 'board_variant.id', '=', 'board_variant_step.variant_id')
                ->with('app_user')
                ->where('board_tracking.board_id', $request['boardId']);
            if (isset($request['track_table_filter'])) {
                $query = $request['track_table_filter'];
                if (isset($query['start_date']) and isset($query['end_date'])) {
                    $trackings = $trackings->whereDate('board_tracking.sent_at', '>=', $query['start_date'])
                        ->whereDate('board_tracking.sent_at', '<=', $query['end_date']);
                }
                if (isset($query['status'])) {
                    $trackings = $trackings->where('board_tracking.status', $query['status']);
                }
                if (isset($query['variantFilter']) && $query['variantFilter'] != -1) {
                    $trackings = $trackings->where('board_tracking.variant_step_id', $query['variantFilter']);
                }
                if (isset($query['deviceType']) && $query['deviceType'] != -1) {
                    $trackings = $trackings->where('board_tracking.device_type', $query['deviceType']);
                }
                if (isset($query['variantType']) && $query['variantType'] != -1) {
                    $trackings = $trackings->where('board_variant.variant_type', $query['variantType']);
                }
                if (isset($query['global'])) {
                    $search = $query['global'];
                    $trackings->where(function ($query) use ($search) {
                        $query->where('board_tracking.track_key', 'LIKE', "%{$search}%");
                        $query->orWhere('board_tracking.email', 'LIKE', "%{$search}%");
                        $query->orWhere('board_tracking.sent_at', 'LIKE', "%{$search}%");
                        $query->orWhere('board_tracking.status', 'LIKE', "%{$search}%");
                        $query->orWhere('board_tracking.viewed_at', 'LIKE', "%{$search}%");
                        $query->orWhere('board_tracking.device_type', 'LIKE', "%{$search}%");
                        $query->orWhere('board_variant.variant_type', 'LIKE', "%{$search}%");
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
                $trackings = $trackings->orderBy('board_tracking.updated_at', 'desc');
            }

            if ($isListing) {
                $trackings = $trackings->get(['board_tracking.*', 'board_variant.variant_type', 'board_variant_step.variant_step_number']);
                $meta = [
                    'pages' => ceil($totalFiltered / $request['limit']),
                    'page' => $request['page'],
                    'total' => $totalFiltered,
                ];
                $response = [
                    'meta' => $meta,
                    'data' => $this->getBoardTrackingResponse($trackings, $request['boardId'])
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

                $fileName = 'board_tracking' . '_' . $request['boardId'] . '_app_users_data_' . Carbon::now()->timestamp;
                $filePath = 'public/company_' . $company->id . '/export/' . $fileName . '.csv';

                $disk = Storage::disk('local');

                $disk->put($filePath, $this->getCSVFileHeaders());

                $chunkSize = config('engagement.api.export.chunk_size');
                $trackings->chunk($chunkSize, function ($rows) use ($request, $disk, $filePath) {
                    $trackRecords = $this->getBoardTrackingResponse($rows, $request['boardId']);
                    $disk->append($filePath, rtrim($this->convertToString($trackRecords)));
                });

                return $this->addResponse(
                    AppStatusCodes::HTTP_OK,
                    AppStatusMessages::SUCCESS,
                    ['company_id' => $company->id, 'file_name' => $fileName],
                    'data'
                );
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

    private function getCSVFileHeaders()
    {
        return "Email, variant_type, row_id, Variant, Track key, Sent At, Status, Device Type, Viewed At, Message";
    }

    private function convertToString($records)
    {
        $str = "";
        foreach ($records as $record) {
            $str .= (!empty($record['email']) ? $record['email'] : 'N/A') . ',';
            $str .= (!empty($record['variant_type']) ? $record['variant_type'] : 'N/A') . ',';
            $str .= (!empty($record['row_id']) ? $record['row_id'] : 'N/A') . ',';
            $str .= (!empty($record['variant']) ? $record['variant'] : 'N/A') . ',';
            $str .= (!empty($record['track_key']) ? $record['track_key'] : 'N/A') . ',';
            $str .= (!empty($record['sent_at']) ? $record['sent_at'] : 'N/A') . ',';
            $str .= (!empty($record['status']) ? $record['status'] : 'N/A') . ',';
            $str .= (!empty($record['device_type']) ? $record['device_type'] : 'N/A') . ',';
            $str .= (!empty($record['viewed_at']) ? $record['viewed_at'] : 'N/A') . ',';
            $str .= (!empty($record['message']) ? $record['message'] : 'N/A') . PHP_EOL;
        }

        return $str;
    }

    private function getBoardTrackingResponse($trackings, $boardId)
    {
        $allVariantsOfCampaign = \App\BoardVariant::where('board_id', $boardId)
            ->pluck('id')->toArray();
        $response = collect();
        foreach ($trackings as $tracking) {
            $lang = Language::where('id', '=', $tracking->language_id)->first();
            $response->push([
                "id" => $tracking->id,
                "variant_type" => $tracking->variant_type,
                /*"variant_id" => $tracking->variant_id,*/
                "row_id" => $tracking->row_id,
                "variantLang" => array_search($tracking->variant_id, $allVariantsOfCampaign) . '-' . $lang->code,
                "variant" => 'Step-' . $tracking->variant_step_number . ': ' . $lang->code,
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

    public function getBoardVariants($appGroupId, $boardId)
    {
        try {
            $exist = Board::where('app_group_id', $appGroupId)
                ->where('id', $boardId)
                ->first();
            if ($exist) {
                $allVariantsOfCampaign = \App\BoardVariant::join('board_variant_step', 'board_variant_step.variant_id', '=', 'board_variant.id')
                    ->where('board_id', $boardId)
                    ->pluck('board_variant_step.id')->toArray();
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

    public function boardStats($id)
    {
        try {
            $board = Board::with('segments')
                ->find($id);

            $checkEmailVariant = \App\BoardVariant::select('id')
                ->where('board_id', $id)
                ->where('variant_type', Board::BOARD_EMAIL_CODE)
                ->first();
            if (empty($checkEmailVariant)) {
                $board->board_has_email_variant = false;
            } else {
                $board->board_has_email_variant = true;
            }

            $boardQueue = BoardQueue::where('board_id', $id)->first();
            $boardTriggerStatus = true;
            if (empty($boardQueue->status) || $boardQueue->status == BoardQueue::STATUS_AVAILABLE) {
                $boardTriggerStatus = false;
            }

            $response = [
                "boardTriggerStatus" => $boardTriggerStatus,
                "campaign" => $board,
                "views" => $this->getViewsCount($board),
                "clicks" => $this->getClicksCount($board),
                "variants" => ''
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

        $board = Board::where('id', $id)
            ->select(['id', 'app_group_id', 'is_remove_cache'])
            ->first();

        try {
            if ($board->is_remove_cache == 1) {
                $statsData = $this->getExpiredStatsByDevice($selectedDevice, $id);
            } else {
                $statsData = $this->fetchBoardTrackingStats($selectedDevice, $board);
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

    public function fetchBoardTrackingStats($selectedDevice, $board)
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
        $totalQuery = DB::select("SELECT count(*) as total from board_tracking where board_id =" . $board->id);
        $statsData['total'] = !empty($totalQuery[0]->total) ? $totalQuery[0]->total : 0;

        $sentQuery = DB::select("SELECT count(*) as sent from board_tracking where board_id =" . $board->id . " AND sent <> 0" . $whereClause);
        $statsData['sent'] = !empty($sentQuery[0]->sent) ? $sentQuery[0]->sent : 0;

        $viewedQuery = DB::select("SELECT count(*) as viewed from board_tracking where board_id =" . $board->id . " AND viewed <> 0" . $whereClause);
        $statsData['viewed'] = !empty($viewedQuery[0]->viewed) ? $viewedQuery[0]->viewed : 0;

        $failedQuery = DB::select("SELECT count(*) as failed from board_tracking where board_id =" . $board->id . " AND status = 'failed'" . $whereClause);
        $statsData['failed'] = !empty($failedQuery[0]->failed) ? $failedQuery[0]->failed : 0;

        $statsData['targetedUsers'] = exportUsers::exportUsers($board->id, 'board', $board->app_group_id, true);

        return $statsData;
    }

    public function getExpiredStatsByDevice($selectedDevice, $boardID)
    {
        $expiredStats = ExpiredBoardStat::where('board_id', $boardID)->first();
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

    private function getViewsCount($board)
    {
        $viewsCountCollection = $board->board_tracking()
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

    private function getClicksCount($board)
    {
        $clicksCountCollection = $board->linkTracking()
            ->select([\DB::raw("COUNT(*) AS count_clicks"), \DB::raw("DATE(created_at) as click_date")])
            ->where('is_board', '=', '1')
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
                if ($query['type']) {
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
            $id = $request['boardId'];
            $Board = Board::find($id);

            if ($Board->is_remove_cache == 1) {
                $expiredStats = ExpiredBoardStat::where('board_id', $id)->first();

                $androidViewsCount = $expiredStats->total_android_viewed;
                $iosViewsCount = $expiredStats->total_ios_viewed;
            } else {
                $androidViewsCount = $this->getViewsByType($Board, "android", $request);
                $iosViewsCount = $this->getViewsByType($Board, "ios", $request);
            }

            $androidClicksCount = $this->getClicksByType($Board, "android", $request);
            $iosClicksCount = $this->getClicksByType($Board, "ios", $request);

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

    public function getViewsClicksChart(\Illuminate\Http\Request $request)
    {
        try {
            $id = $request['boardId'];
            $type = $request->get('type');
            $Board = Board::find($id);
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
                        $viewsCount = $this->getViewsByType($Board, $device['value'], $request);
                        $options->push([
                            "y" => $viewsCount, "label" => $date
                        ]);
                    }
                    if ($type == "clicks") {
                        $clicksCount = $this->getClicksByType($Board, $device['value'], $request);
                        $options->push([
                            "y" => $clicksCount, "label" => $date
                        ]);
                    }

                    if ($type == "click_through") {
                        $viewsCount = $this->getViewsByType($Board, $device['value'], $request);
                        $clicksCount = $this->getClicksByType($Board, $device['value'], $request);
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
            $id = $request['board_id'];
            $board = Board::find($id);

            $totalUsersCount = AppUsers::where('app_group_id', $board->app_group_id)->count();

            $countriesData = DB::select("SELECT 
                            COUNT(*)/{$totalUsersCount} * 100 as `y`, country as `name`
                        FROM
                            app_user
                        WHERE
                            app_group_id = {$board->app_group_id}
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
            $id = $request['board_id'];
            $board = Board::find($id);

            $activityData = [];

            $recipients = 0;
            if ($board->is_remove_cache == 0) {
                $recipients = BoardTracking::where('board_id', $board->id)->count();
                $opens = BoardTracking::where('board_id', $board->id)->where('viewed', '<>', 0)->count();
                $uniqueOpens = BoardTracking::where('board_id', $board->id)->where('viewed', '<>', 0)->distinct('row_id')->count('row_id');
            } else {
                $recipients = (ExpiredBoardStat::select('total_trackings')->where('board_id', $board->id)->first())->total_trackings;
                $opens = (ExpiredBoardStat::select('total_viewed')->where('board_id', $board->id)->first())->total_viewed;
                $uniqueOpens = (ExpiredBoardStat::select('total_unique_viewed')->where('board_id', $board->id)->first())->total_unique_viewed;
            }


            $unopens = !(empty($opens) && !empty($recipients)) ? $recipients - $opens : 0;


            $clicks = LinkTrackings::where('rec_id', $board->id)->where('rec_type', 'email')->count();


            $bounces = AppUsers::where('app_group_id', $board->app_group_id)->where('is_bounced', 1)->count();

            array_push($activityData, ['y' => $bounces, 'label' => 'Bounces']);
            array_push($activityData, ['y' => $clicks, 'label' => 'Clicks']);
            array_push($activityData, ['y' => $unopens, 'label' => 'Unopens']);
            array_push($activityData, ['y' => $opens, 'label' => 'Opens']);
            array_push($activityData, ['y' => $recipients, 'label' => 'Recipients']);


            $uniqueClicks = LinkTrackings::where('rec_id', $board->id)->where('rec_type', 'email')->distinct('row_id')->count('row_id');

            $stats = [];
            $stats['opened'] = $opens;
            $stats['openedPercentage'] = round($opens / $recipients * 100, 2);
            $stats['notOpened'] = round($unopens, 2);
            $stats['bounced'] = $bounces;
            $stats['bouncedPercentage'] = round($bounces / $recipients * 100, 2);
            $stats['uniqueOpens'] = $uniqueOpens;
            $stats['uniqueClicks'] = $uniqueClicks;
            $stats['clicksPercentage'] = round($clicks / $recipients * 100, 2);
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
            $id = $request['board_id'];
            $board = Board::find($id);
            $linkActivityData = DB::select("SELECT count(*) as total, actual_url FROM link_tracking where rec_id=" . $board->id . " group by actual_url");

            $lastTenOpenedData = [];
            if ($board->is_remove_cache == 0) {
                $query = DB::select("SELECT row_id FROM board_tracking where board_id=" . $board->id . " AND viewed > 0 ORDER BY viewed_at DESC limit 10");
                $lastTenRowIDs = [];
                foreach ($query as $item) {
                    array_push($lastTenRowIDs, $item->row_id);
                }

            } else {
                $lastTenRowIDs = (ExpiredBoardStat::select('last_ten_row_ids')->where('board_id', $board->id)->first())->last_ten_row_ids;
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
        $id = $request->get('board_id');
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

        $board = Board::find($id);

        $lastTenOpenedData = [];
        if ($board->is_remove_cache == 0) {
            $query = DB::select("SELECT * FROM board_tracking where board_id=" . $board->id . " AND viewed > 0 ORDER BY viewed_at DESC limit 10");
            $lastTenRowIDs = [];
            foreach ($query as $item) {
                array_push($lastTenRowIDs, $item->row_id);
            }

        } else {
            $lastTenRowIDs = (ExpiredBoardStat::select('last_ten_row_ids')->where('board_id', $board->id)->first())->last_ten_row_ids;
            $lastTenRowIDs = !empty($lastTenRowIDs) ? json_decode($lastTenRowIDs) : '';
        }

        $lastTenOpenedData = AppUsers::select(['row_id', 'app_group_id', 'company_id', 'user_id', 'app_id', 'username', 'firstname', 'lastname', 'email', 'timezone', 'latitude', 'longitude', 'country', 'last_login', 'enable_notification', 'email_notification', 'status'])
            ->whereIn('row_id', $lastTenRowIDs)
            ->get();

        $company = Auth::user();
        $files = File::cleanDirectory(storage_path('app/public/company_' . $company->id . '/export/'));

        $fileName = 'board_' . $id . '_last_10_opened_app_users_data_' . Carbon::now()->timestamp;
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
        $board = Board::find($id);

        $linkActivityData = DB::select("SELECT count(*) as total, actual_url FROM link_tracking where rec_id=" . $board->id . " group by actual_url");

        $company = Auth::user();
        $files = File::cleanDirectory(storage_path('app/public/company_' . $company->id . '/export/'));

        $fileName = 'board_' . $id . '_link_activity_data_' . Carbon::now()->timestamp;
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

    private function getViewsByType($board, $type, $request)
    {
        $viewsCountCollection = Board::join('board_tracking', 'board_tracking.board_id', '=', 'board.id')
            ->where('board_tracking.device_type', $type)
            ->where('board_tracking.board_id', $request['boardId'])
            ->where('board_tracking.viewed', '!=', 0);
        if (!empty($request['start_date']) and !empty($request['end_date'])) {
            $viewsCountCollection = $viewsCountCollection->whereDate('board_tracking.viewed_at', '>=', $request['start_date'])
                ->whereDate('board_tracking.viewed_at', '<=', $request['end_date']);
        }
        if (isset($request['date'])) {
            $viewsCountCollection = $viewsCountCollection->whereDate('board_tracking.viewed_at', $request['date']);
        }
        $viewsCountCollection = $viewsCountCollection->count();
        return $viewsCountCollection;
    }

    private function getClicksByType($board, $type, $request)
    {
        $viewsCountCollection = Board::join('link_tracking', 'link_tracking.rec_id', '=', 'board.id')
            ->where('link_tracking.device_type', $type)
            ->where('link_tracking.rec_id', $request['boardId'])
            ->where('link_tracking.is_board', 1)
            ->where('link_tracking.viewed', 1);

        if (!empty($request['start_date']) and !empty($request['end_date'])) {
            $viewsCountCollection = $viewsCountCollection->whereDate('link_tracking.created_at', '>=', $request['start_date'])
                ->whereDate('link_tracking.created_at', '<=', $request['end_date']);
        }

        if (isset($request['date'])) {
            $viewsCountCollection = $viewsCountCollection->whereDate('link_tracking.created_at', $request['date']);
        }
        $viewsCountCollection = $viewsCountCollection
            ->count();
        return $viewsCountCollection;
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
            new \DateTime(Carbon::now()->subDays(7)->format('Y-m-d')),
            new \DateInterval('P1D'),
            new \DateTime(Carbon::now()->addDay(1)->format('Y-m-d'))
        );

        $dates = [];
        foreach ($period as $key => $value) {
            array_push($dates, $value->format('Y-m-d'));
        }

        return $dates;
    }

    public function resendNotification(\Illuminate\Http\Request $request)
    {
        try {
            $message = '';
            $data = $this->parseResponse($request);
            $boardTracking = BoardTracking::where('id', $data['id'])->first();
            if ($boardTracking) {
                $boardId = $boardTracking->board_id;
                $row_id = $boardTracking->row_id;
                $app_user_token_id = $boardTracking->app_user_token_id;
                $email = $boardTracking->email;
                $server_key = $boardTracking->firebase_key;
                $device_key = $boardTracking->device_key;
                $payload = \GuzzleHttp\json_decode($boardTracking->payload, true);
                $track_key = $boardTracking->track_key;
                $device_type = $boardTracking->device_type;
                $job = $boardTracking->job;
                if ($job == 'PushJobWorker') {
                    \Artisan::call('config:cache');
                    \Config::set('fcm.http.server_key', $server_key);
                    if ($device_type == 'ios' || $device_type == 'android') {
                        $payload['notification']['link'] = '';
                    }
                    $dataArray = array(
                        'data' => $payload['data']
                    );
                    $notifications = new NotificationController($device_key, $payload['notification'], $dataArray, $server_key, '1');
                    $response = $notifications->sendNotification();
                    if (isset($response['status'])) {
                        throw new \Exception($response['message']);
                    } else {
                        if ($response['numberSuccess']) {
                            $message = 'Notification Send Successfully.';
                            $trackingLog = array(
                                'board_tracking_id' => $data['id'],
                                'status' => 'completed',
                                'message' => 'Notification Send Successfully.'
                            );
                            $trackingdata = array(
                                'id' => $data['id'],
                                'status' => 'completed'
                            );
                            $this->updateTrackingStatus($trackingdata);
                        } else {
                            throw new \Exception('Failed, notification not send.');
                        }
                    }
                } else {
                    $emailArray = array(
                        'subject' => $payload['data']['email_subject'],
                        'from_email' => $payload['data']['email_from'],
                        'message' => $payload['data']['email_body'],
                        'email' => $payload['data']['to_email'],
                        'firstname' => '',
                        'lastname' => ''
                    );
                    $notification = new Notifications\SendEmail($row_id, $emailArray);
                    $response = $notification->send();
                    if ($response['status'] == 'success') {
                        $trackingLog = array(
                            'board_tracking_id' => $data['id'],
                            'status' => 'completed',
                            'message' => 'Email Send Successfully.'
                        );
                        $trackingdata = array(
                            'id' => $data['id'],
                            'status' => 'completed'
                        );
                        $message = 'Email Send Successfully.';
                        $this->updateTrackingStatus($trackingdata);
                    } else {
                        throw new \Exception($response['data']);
                    }
                }
                return $this->addResponse(
                    AppStatusCodes::HTTP_OK,
                    'success',
                    $message,
                    'data'
                );
            } else {
                throw new \Exception('Failed, not a valid campaign.');
            }
        } catch (\Exception $exception) {
            $trackingdata = array(
                'id' => $request['id'],
                'status' => 'failed'
            );
            $this->updateTrackingStatus($trackingdata);
            return $this->addResponse(
                AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                'error',
                [$exception->getMessage()],
                'error'
            );
        }
    }

    public function updateTrackingStatus($data)
    {
        $result = BoardTracking::where('id', $data['id'])->update([
            'status' => $data['status']
        ]);
        return $result;
    }

    public function boardUserStatsSteps($boardId)
    {
        try {
            $board_tracking = \DB::table('board_tracking')
                ->join('board_variant_step', 'board_tracking.variant_step_id', '=', 'board_variant_step.id')
                ->join('board_variant', 'board_variant_step.variant_id', '=', 'board_variant.id')
                ->select('board_tracking.variant_step_id', 'board_variant_step.variant_id', 'board_variant.variant_type')
                ->where('board_tracking.board_id', $boardId)
                ->orderBy('board_tracking.variant_step_id')
                ->distinct()
                ->get()
                ->toArray();

            $response = array();
            if (count($board_tracking) > 0) {
                $collection = collect($board_tracking);
                $plucked = $collection->pluck('variant_type', 'variant_id');
                $variantsData = $plucked->all();

                $variantStepsData = [];
                foreach ($board_tracking as $tracking) {
                    $variantStepsData[$tracking->variant_id][] = $tracking->variant_step_id;
                }

                $temp = array();
                foreach ($variantsData as $variantId => $variantType) {
                    $temp[] = [
                        "id" => $variantId,
                        "type" => $variantType,
                        "steps" => $variantStepsData[$variantId]

                    ];
                }

                $response = [
                    'variants' => $variantsData,
                    'variantsStepsArr1' => $variantStepsData,
                    'variantsStepsArr2' => $temp
                ];
            }

            return $this->addResponse(AppStatusCodes::HTTP_OK, AppStatusMessages::SUCCESS, $response, 'data', []);
        } catch (\Exception $exception) {
            return $this->addResponse(AppStatusCodes::HTTP_NOT_FOUND, 'error', ['Unable to load data'], $exception->getMessage());
        }
    }


    /**
     * @param $boardId
     * @param $appGroupId
     * @return \Illuminate\Http\JsonResponse
     */
    public function boardUserStats($boardId, $appGroupId)
    {
        try {
            // get board segments
            $segments = (new \App\Cache\BoardSegmentCache)->getBoardSegmentsCache($boardId);
            if (!empty($segments)) {
                $collection = collect();
                foreach ($segments as $segmentId) {
                    $segmentRowIds = (new \App\Cache\AppGroupSegmentCache)->getAppGroupSegmentRowsCache($appGroupId, $segmentId);
                    $collection = $collection->merge($segmentRowIds);
                }

                // get unique row ids and remove old collection
                $uniqueRowIds = $collection->unique();
                unset($collection);
                $segmentUserCount = $uniqueRowIds->count();

                // make chunks of rowIds
                $chunkSize = config('engagement.api.export.chunk_size');
                $chunks = $uniqueRowIds->chunk($chunkSize);
                unset($uniqueRowIds);

                $response = [
                    'segment_user_count' => $segmentUserCount,
                    "disable_notification" => 0,
                    "not_login_user" => 0,
                    "null_firebase_key" => 0,
                    "null_device_token" => 0,
                    "null_email" => 0,
                    "unsubscribe_email" => 0,
                    "revoked" => 0,
                ];

                $appsWithNullFirebaseKey = \App\Apps::select(['id', 'app_id', 'platform'])
                    ->where('app_group_id', $appGroupId)
                    ->where('firebase_api_key', null)
                    ->where('is_active', 1)
                    ->get();

                foreach ($chunks as $chunk) {
                    foreach ($chunk as $rowId) {
                        // get user row data from cache
                        $cache_key = "app_group_id_" . $appGroupId . "_row_id_" . $rowId;
                        $userRowData = \Cache::get($cache_key);
                        if ($userRowData) {
                            $userRowData = json_decode($userRowData, true);
                            if (isset($userRowData[0])) {

                                if ($userRowData[0]['enable_notification'] == 0) {
                                    $response['disable_notification']++;
                                }
                                if ($userRowData[0]['apps_users_tokens']['logged_in'] == 0) {
                                    $response['not_login_user']++;
                                }
                                if ($userRowData[0]['email_notification'] == 0) {
                                    $response['unsubscribe_email']++;
                                }
                                if (empty($userRowData[0]['apps_users_tokens']['device_token'])) {
                                    $response['null_device_token']++;
                                }
                                if (empty($userRowData[0]['email'])) {
                                    $response['null_email']++;
                                }
                                if ($userRowData[0]['apps_users_tokens']['revoked'] == 1) {
                                    $response['revoked']++;
                                }
                                foreach ($appsWithNullFirebaseKey as $app) {
                                    if ($app->app_id == $userRowData[0]['app_id'] && $app->platform == $userRowData[0]['apps_users_tokens']['device_type']) {
                                        $response['null_firebase_key']++;
                                    }
                                }
                            }
                        }
                    }
                }

                return $this->addResponse(AppStatusCodes::HTTP_OK, AppStatusMessages::SUCCESS, $response, 'data', []);
            } else {
                throw new \Exception('Board segment is empty');
            }
        } catch (\Exception $exception) {
            return $this->addResponse(AppStatusCodes::HTTP_NOT_FOUND, 'error', ['Unable to load data'], $exception->getMessage());
        }
    }
}