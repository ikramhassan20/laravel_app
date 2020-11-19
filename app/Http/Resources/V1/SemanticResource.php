<?php

namespace App\Http\Resources\V1;

use App\Board;
use App\BoardQueue;
use App\Components\AppStatusCodes;
use App\Components\AppStatusMessages;
use App\Components\Campaigns\CampaignTrackingServiceValidatorRequest;
use App\Components\ParseResponse;
use App\Concerns\exportUsers;
use App\Concerns\tagsCount;
use App\Helpers\BoardValidation;
use App\Http\Resources\Contracts\ProcessResourceDataContract;
use App\Http\Resources\Contracts\ResourcesContract;
use App\Http\Resources\ResourcesSteps;
use App\Http\Resources\V1\Boards\AudienceStep;
use App\Http\Resources\V1\Boards\BasicStep;
use App\Http\Resources\V1\Boards\BoardCompose;
use App\Http\Resources\V1\Boards\BoardDeleteVariantSteps;
use App\Http\Resources\V1\Boards\BoardVariant;
use App\Http\Resources\V1\Boards\DeliveryStep;
use App\Http\Resources\V1\Boards\SettingStep;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Artisan;

class SemanticResource implements ResourcesContract, ProcessResourceDataContract
{
    use ParseResponse, ResourcesSteps;


    public function all(Request $request)
    {
        try {
            $companyId = $request->user()->id;
            $id = Auth::user()->id;
            if ($companyId != $id) {
                throw new \Exception('Invalid User');
            }
            $response = (new Boards\boardPaginateResponse())->process($request);

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
                ['Unable to load board data'],
                $exception->getMessage()
            );
        }
    }

    public function getSideFilters($appGroupId)
    {
        try {

//            $boardType = [
//
//                "column" => "Board Type",
//                "children" => [
//                    [
//                        "parent" => "board_type",
//                        "value" => "email"
//                    ],
//                    [
//                        "parent" => "board_type",
//                        "value" => "push"
//                    ],
//                    [
//                        "parent" => "board_type",
//                        "value" => "inapp"
//                    ]
//                ]
//            ];
            $schedule = [

                "column" => "Schedule",
                "children" => [
                    [
                        "parent" => "schedule_type",
                        "value" => "weekly"
                    ],
                    [
                        "parent" => "schedule_type",
                        "value" => "daily"
                    ],
                    [
                        "parent" => "schedule_type",
                        "value" => "once"
                    ]
                ]
            ];
            $status = [

                "column" => "Status",
                "children" => [
                    [
                        "parent" => "status",
                        "value" => "active"
                    ],
                    [
                        "parent" => "status",
                        "value" => "draft"
                    ],
                    [
                        "parent" => "status",
                        "value" => "suspended"
                    ],
                    [
                        "parent" => "status",
                        "value" => "expired"
                    ],
                ]
            ];

            $boardSideFilters = [];
            $obj = (object)[];
            $obj->column = "Tags";
            $obj->children = [];
            $tags = tagsCount::findTagsCount($appGroupId, 'board');

            foreach ($tags as $tag) {
                $tagObj = (object)[];
                $tagObj->parent = "tags";
                $tagObj->value = $tag->tags;
                $obj->children[] = $tagObj;
            }

            $boardSideFilters[] = clone $obj;
            $boardSideFilters[] = $schedule;
            $boardSideFilters[] = $status;

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $boardSideFilters,
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

    public function create(\Illuminate\Http\Request $request)
    {
        try {
            $companyId = $request->user()->id;
            $id = Auth::user()->id;
            if ($companyId != $id) {
                throw new \Exception('Invalid User');
            }
            $data = $this->parseResponse($request);

            $Board = $this->process($data, new Board());


            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $Board,
                'data'
            );
        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                'error',
                [AppStatusMessages::CANNOT_CREATE_RECORD],
                $exception
            );

        }

    }

    public function get($boardId)
    {
        try {
            $appGroupId = \Request::user()->currentAppGroup()->id;
            $boardExist = Board::where("id", $boardId)
                ->where("app_group_id", $appGroupId)
                ->first();
            if (!$boardExist) {
                return $this->addResponse(
                    AppStatusCodes::HTTP_OK,
                    'error',
                    [
                        "status" => false,
                        "message" => "Board not found"
                    ],
                    'data'
                );
            }
            $stepArr = [];
            switch (strtolower($boardExist->step)) {
                case 'general':
                    $stepArr[] = (new Boards\BasicStep);
                    break;

                case 'delivery':
                    $stepArr[] = (new Boards\BasicStep);
                    $stepArr[] = (new Boards\DeliveryStep);
                    break;

                case 'target':
                    $stepArr[] = (new Boards\BasicStep);
                    $stepArr[] = (new Boards\DeliveryStep);
                    $stepArr[] = (new Boards\AudienceStep);
                    break;
                case 'setting':
                    $stepArr[] = (new Boards\BasicStep);
                    $stepArr[] = (new Boards\DeliveryStep);
                    $stepArr[] = (new Boards\AudienceStep);
                    $stepArr[] = (new Boards\SettingStep);
                    $stepArr[] = (new Boards\BoardCompose);
                    break;
                case 'preview':
                    $stepArr[] = (new Boards\BasicStep);
                    $stepArr[] = (new Boards\DeliveryStep);
                    $stepArr[] = (new Boards\AudienceStep);
                    $stepArr[] = (new Boards\SettingStep);
                    $stepArr[] = (new Boards\BoardCompose);
                    break;
                default:
                    $stepArr[] = (new Boards\BasicStep);
                    $stepArr[] = (new Boards\DeliveryStep);
                    $stepArr[] = (new Boards\AudienceStep);
                    $stepArr[] = (new Boards\SettingStep);
                    $stepArr[] = (new Boards\BoardCompose);
                    break;

            }


            $getSteps = [];
            foreach ($stepArr as $step) {
                $getSteps[] = $step->getStep($boardId);
            }
            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $getSteps,
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

    public function exportUsers($boardId, $appGroupId)
    {
        try {
            $checkBoard = Board::find($boardId);
            if(!isset($checkBoard)){
                return $this->addResponse(
                    AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                    'error',
                    ['No board found.'],
                    'error'
                );
            }
            if($checkBoard->app_group_id != $appGroupId){
                return $this->addResponse(
                    AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                    'error',
                    ['Invalid user.'],
                    'error'
                );
            }
            $users = exportUsers::exportUsers($boardId, 'board', $appGroupId);

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

    public function process($request, \Illuminate\Database\Eloquent\Model $model)
    {
        switch (strtolower($request['step'])) {
            case 'general':
                $class = (new Boards\BasicStep);
                break;
            case 'delivery':
                $class = (new Boards\DeliveryStep);
                break;
            case 'target':
                $class = (new Boards\AudienceStep);
                break;
            case 'setting':
                $class = (new Boards\SettingStep);
                break;
            case 'variant':
                $class = (new Boards\BoardVariant);
                break;
            case 'compose':
                $class = (new Boards\BoardCompose);
                break;
            case 'deletevariantsteps':
                $class = (new Boards\BoardDeleteVariantSteps);
                break;
            case 'deletevariant':
                $class = (new Boards\BoardDeleteVariant);
                break;
            case 'preview':
                $class = (new Boards\BoardPreview);
                break;
        }

        return $class->process($request, $model);
    }

    public function show(\Illuminate\Database\Eloquent\Model $model)
    {

    }

    public function update(\Illuminate\Http\Request $request, \Illuminate\Database\Eloquent\Model $model)
    {
        try {
            $companyId = $request->user()->id;
            $id = Auth::user()->id;
            if ($companyId != $id) {
                throw new \Exception('Invalid User');
            }
            $data = $this->parseResponse($request);

            $board = $this->process($data, $model);

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $board,
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

    public function remove(\Illuminate\Database\Eloquent\Model $model)
    {

    }

    /*
  * trackingService
  *
  */
    public function trackingService(Request $request)
    {
        try {
            $data = $this->parseResponse($request);
            $appId = $request->header('app-id');
            $appName = $request->header('app-name');
            $devicetype = $request->header('device-type');
            $version = $request->header('app-version');
            $build = $request->header('app-build');
            $devicetoken = $request->header('device-token');
            $mode = $data['mode'];
            $board_type = strtolower($data['type']); // push/inapp
            $data['device_token'] = $devicetoken;
            $validator = new CampaignTrackingServiceValidatorRequest($data);
            if (!empty($validator->errors)) {
                return $this->addResponse(
                    AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                    'error',
                    $validator->errors,
                    'error'
                );
            }
            $userParam = array(
                'appId' => $appId,
                'userId' => $data['user_id'],
                'appName' => $appName
            );
            $boardTracking = (new BoardValidation())->BoardTrackkeyValidator($data);
            if ($boardTracking) {
                $linkTrackingObj = array(
                    'rec_type' => $board_type,
                    'rec_id' => $boardTracking->board_id,
                    'row_id' => $boardTracking->row_id,
                    'actual_url' => (isset($data['action_url'])) ? $data['action_url'] : "",
                    'created_date' => Carbon::now(),
                    'device_type' => $devicetype,
                    'ip_address' => '',
                    'user_agent' => '',
                    'viewed' => '1',
                    'board_tracking_viewed' => $boardTracking->viewed,
                    'track_key' => $data['track_key'],
                    'device_token' => $data['device_token'],
                    "is_board" => true
                );
                switch ($mode) {
                    case 'viewed':
                        $response = (new BoardValidation())->updateLinkTrackingStatus($boardTracking->viewed, $data);
                        $response = ['Tracking has been saved.'];
                        break;
                    case 'clicked':
                        $response = (new BoardValidation())->insertLinkTracking($mode, $linkTrackingObj);
                        break;
                    case 'both':
                        $response = (new BoardValidation())->insertLinkTracking($mode, $linkTrackingObj);
                        break;
                }
            }
            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $response,
                'data'
            );
        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_NOT_FOUND,
                'error',
                ['Unable to load tracking service.'],
                $exception->getMessage()
            );
        }
    }


    public function boardQueuesListing(Request $request)
    {
        try {

            $queryChain = BoardQueue::join('board', 'board.id', '=', 'board_queue.board_id')
                ->leftjoin('app_group', 'app_group.id', '=', 'board.app_group_id')
                ->leftjoin('users', 'users.id', '=', 'app_group.company_id');
            $totalCount = clone $queryChain;
            $totalCount = $totalCount->count();
            if ($request['sideFilters'] != null && $request['sideFilters'] != []) {
                $queryChain->where('users.id', '=', $request['sideFilters']);
            }

            if ($request['query'] != null) {
                $search = $request['query'];
                $columns = $request['columns'];
                $queryChain->where(function ($query) use ($search, $columns) {
                    $query->where('board_queue.id', 'LIKE', "%{$search}%");
                    $query->orWhere('board_queue.status', 'LIKE', "%{$search}%");
                    $query->orWhere('board_queue.details', 'LIKE', "%{$search}%");
                    $query->orWhere('board_queue.error_message', 'LIKE', "%{$search}%");
                    $query->orWhere('board_queue.created_at', 'LIKE', "%{$search}%");
                    $query->orWhere('users.name', 'LIKE', "%{$search}%");
                });
            }
            $totalFiltered = clone $queryChain;
            $totalFiltered = $totalFiltered->count();
            isset($request["orderBy"]) ? $queryChain->orderBy($request["orderBy"], $request["ascending"] == 1 ? 'desc' : 'asc') : $queryChain->orderBy('updated_at', 'desc');
            $data = $queryChain->offset(($request['page'] - 1) * $request['limit'])
                ->limit($request['limit'])
                ->get(['users.name as company_name', 'board_queue.*']);
            $meta = [
                'pages' => ceil($totalFiltered / $request['limit']),
                'page' => $request['page'],
                'total' => $totalFiltered,
            ];
            $response = [
                'meta' => $meta,
                'data' => $data
            ];
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

    public function updateBoardQueueStatus(Request $request)
    {
        try {
            $attr = $request->all();
            $queue = BoardQueue::find($attr['id']);

            if (empty($queue)) {
                return $this->addResponse(
                    AppStatusCodes::HTTP_NOT_FOUND,
                    'error',
                    ['Board queue not found.'],
                    'error'
                );
            }

            if (isset($attr['status']) AND $attr['status'] == 'Available') {
                $queue->update([
                    'status' => $attr['status']
                ]);
            }

            if (isset($attr['status']) AND $attr['status'] == 'Processing') {
                Artisan::call('backend:board:queue', [
                    'id' => $attr['id']
                ]);
            }

            return response()->json('Status updated successfully.');
        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_NOT_FOUND,
                'error',
                ['Unable to load board queues data'],
                $exception->getMessage()
            );
        }
    }



}