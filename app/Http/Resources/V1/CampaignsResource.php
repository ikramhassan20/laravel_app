<?php

namespace App\Http\Resources\V1;

use App\AppUserActivity;
use App\Cache\AppGroupSegmentCache;
use App\Cache\CampaignSegmentCache;
use App\Campaign;
use App\CampaignAction;
use App\CampaignQueue;
use App\CampaignTracking;
use App\CampaignTrackingLog;
use App\Components\Action\ActionListingValidator;
use App\Components\AppStatusCodes;
use App\Components\AppStatusMessages;
use App\Components\CampaignDispatchProcess;
use App\Components\Campaigns\CampaignActionTriggerValidatorRequest;
use App\Components\Campaigns\CampaignConversionTriggerValidatorRequest;
use App\Components\Campaigns\CampaignInAppTriggerValidatorRequest;
use App\Components\Campaigns\CampaignTrackingServiceValidatorRequest;
use App\Components\ParseResponse;
use App\Components\RenderPaginatedResponse;
use App\Concerns\exportUsers;
use App\Concerns\tagsCount;
use App\Helpers\CampaignValidation;
use App\Helpers\CommonHelper;
use App\Http\Controllers\NotificationController;
use App\Http\Resources\Contracts\ProcessResourceDataContract;
use App\Http\Resources\Contracts\ResourcesContract;
use App\Http\Resources\ResourcesSteps;
use App\Jobs\EmailJobWorker;
use App\Jobs\PushJobWorker;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Artisan;
use App\CampaignCapRule;

class CampaignsResource implements ResourcesContract, ProcessResourceDataContract
{
    use ParseResponse, ResourcesSteps, RenderPaginatedResponse, tagsCount, exportUsers;

    /**
     * Get list of campaigns.
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
            $response = (new Campaigns\campaignPaginateResponse())->process($request);

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
                ['Unable to load campaign data'],
                $exception->getMessage()
            );
        }
    }

    /**
     * Create a new campaign.
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
            $data = $this->parseResponse($request);

            $campaign = $this->process($data, new Campaign());

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $campaign,
                'data'
            );
        } catch (\Exception $exception) {
            if ($exception->getCode() == AppStatusCodes::DUPLICATE_SQL_ENTRY_COLUMN) {
                return $this->addResponse(
                    AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                    'error',
                    [AppStatusMessages::DUPLICATE_ENTRY],
                    'error'
                );
            } else {
                return $this->addResponse(
                    AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                    'error',
                    [AppStatusMessages::CANNOT_CREATE_RECORD],
                    $exception->getMessage()
                );
            }

        }
    }

    /**
     * Process campaign steps data.
     *
     * @param array|\Illuminate\Http\Request $request
     * @param \Illuminate\Database\Eloquent\Model $model
     *
     * @throws \Exception
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function process($request, \Illuminate\Database\Eloquent\Model $model)
    {
        switch (strtolower($request['step'])) {
            case 'general':
                $class = (new Campaigns\GeneralStep);
                break;
            case 'compose':
                $class = (new Campaigns\ComposeStep);
                break;
            case 'delivery':
                $class = (new Campaigns\DeliveryStep);
                break;
            case 'target':
                $class = (new Campaigns\TargetStep);
                break;
            case 'conversion':
                $class = (new Campaigns\ConversionStep);
                break;
            case 'preview':
                $class = (new Campaigns\PreviewStep);
                break;
        }

        return $class->process($request, $model);
    }

    /**
     * Update data for a campaign.
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
            $data = $this->parseResponse($request);

            $campaign = $this->process($data, $model);

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $campaign,
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

    public function get($campaignId)
    {
        try {

            $appGroupId = \Request::user()->currentAppGroup()->id;
            $campaignExist = Campaign::where("id", $campaignId)
                ->where("app_group_id", $appGroupId)
                ->first();

            if (!$campaignExist) {

                return $this->addResponse(
                    AppStatusCodes::HTTP_OK,
                    'error',
                    [
                        "status" => false,
                        "message" => "Campaign not found"
                    ],
                    'data'
                );
            }


            $stepArr = [];
            switch (strtolower($campaignExist->step)) {
                case 'general':
                    $stepArr[] = (new Campaigns\GeneralStep);
                    break;
                case 'compose':
                    $stepArr[] = (new Campaigns\GeneralStep);
                    $stepArr[] = (new Campaigns\ComposeStep);
                    break;
                case 'delivery':
                    $stepArr[] = (new Campaigns\GeneralStep);
                    $stepArr[] = (new Campaigns\ComposeStep);
                    $stepArr[] = (new Campaigns\DeliveryStep);
                    break;
                case 'target':
                    $stepArr[] = (new Campaigns\GeneralStep);
                    $stepArr[] = (new Campaigns\ComposeStep);
                    $stepArr[] = (new Campaigns\DeliveryStep);
                    $stepArr[] = (new Campaigns\TargetStep);
                    break;
                default:
                    $stepArr[] = (new Campaigns\GeneralStep);
                    $stepArr[] = (new Campaigns\ComposeStep);
                    $stepArr[] = (new Campaigns\DeliveryStep);
                    $stepArr[] = (new Campaigns\TargetStep);
                    $stepArr[] = (new Campaigns\ConversionStep);
            }

            $getSteps = [];
            foreach ($stepArr as $step) {
                $getSteps[] = $step->getStep($campaignId);
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
                $exception->getMessage(),
                [AppStatusMessages::DUPLICATE_ENTRY]
            );
        }
    }

    public function getSideFilters($appGroupId)
    {
        try {

            $campaignType = [

                "column" => "Campaign Type",
                "children" => [
                    [
                        "parent" => "campaign_type",
                        "value" => "email"
                    ],
                    [
                        "parent" => "campaign_type",
                        "value" => "push"
                    ],
                    [
                        "parent" => "campaign_type",
                        "value" => "inapp"
                    ]
                ]
            ];
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

            $campaignSideFilters = [];
            $obj = (object)[];
            $obj->column = "Tags";
            $obj->children = [];
            $tags = tagsCount::findTagsCount($appGroupId, 'campaign');

            foreach ($tags as $tag) {
                $tagObj = (object)[];
                $tagObj->parent = "tags";
                $tagObj->value = $tag->tags;
                $obj->children[] = $tagObj;
            }

            $campaignSideFilters[] = clone $obj;
            $campaignSideFilters[] = $campaignType;
            $campaignSideFilters[] = $schedule;
            $campaignSideFilters[] = $status;

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $campaignSideFilters,
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

    public function exportUsers($campaignId, $appGroupId)
    {
        try {
           $campaignCheck = Campaign::where('id','=',$campaignId)->where('app_group_id','=',$appGroupId)->get();
           if(count($campaignCheck)>0){
               $users = exportUsers::exportUsers($campaignId, 'campaign', $appGroupId);
               return $this->addResponse(
                   AppStatusCodes::HTTP_OK,
                   'success',
                   $users,
                   'data'
               );
           }else{
               throw new \Exception('Invalid user.');
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

    /*
     * Campaign apis
     *
     * Campaign Action Trigger api
     *
     */
    /**
     * @param Request $request
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function actionTrigger(Request $request, \Illuminate\Database\Eloquent\Model $model)
    {
        try {
            $user = $request->user();
            $companyid = $user->id;
            $userArray = array();
            $data = $this->parseResponse($request);
            $appId = $request->header('app-id');
            $appName = $request->header('app-name');
            $devicetype = $request->header('device-type');
            $version = $request->header('app-version');
            $build = $request->header('app-build');
            $deviceToken = $request->header('device-token');
            $validator = new CampaignActionTriggerValidatorRequest($data);
            if (!empty($validator->errors)) {
                return $this->addResponse(
                    AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                    'error',
                    $validator->errors,
                    'error'
                );
            }
            if(count($data['action_array']) < 1){
                return $this->addResponse(
                    AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                    'error',
                    ['Attribute code or collection is required.'],
                    'error'
                );
            }
            $userParam = array(
                'appId' => $appId,
                'user_id' => $data['user_id'],
                'device_type' => $devicetype,
                'appName' => $appName,
                'company_id' => $companyid
            );
            $appGroupId = (new CampaignValidation())->getAppGroupId($userParam);
            //dd($appGroupId);
            if ($appGroupId) {
                $data['app_group_id'] = $appGroupId->app_group_id;
                $data['company_id'] = $appGroupId->company_id;
                $userParam['company_id'] = $appGroupId->company_id;
                $getUsersRowIds = (new CommonHelper())->getRowIds($userParam);
                //dd($getUsersRowIds, $data, $getUsersRowIds );
                if ($getUsersRowIds) {

                    $campaignId = (new CampaignValidation())->attributeCollection($data['app_group_id'], $data['action_array']);
                    //$lookupValidator = (new CampaignValidation())->attributeValidator($data['app_group_id'], $data['code']);
                    //if ($lookupValidator) {
                    //    $campaignId = (new CampaignValidation())->CampaignActionValidator($lookupValidator, $data['value']);

                        if ($campaignId) {
                            $finalpayload=[];$payload_error = [];$delivery_error = [];
                            for ($val = 0; $val < count($campaignId); $val++) {

                                $userArray = self::getCampaignUsers($campaignId[$val]['campaign_id'], $appGroupId);

                                if (in_array($getUsersRowIds['row_id'], $userArray)) {
                                    //foreach ($campaignId as $row) {
                                    //dd($row['campaign_id'], $row);
                                    $payload = CampaignDispatchProcess::processDispatcher([$getUsersRowIds['row_id']], $campaignId[$val]['campaign_id']);
                                    //dump($getUsersRowIds['row_id'], $row['campaign_id'], $payload);
                                    $_finalpayload = \GuzzleHttp\json_decode($payload, true);

                                    if (isset($_finalpayload) && $_finalpayload['status'] == AppStatusCodes::HTTP_OK) {
                                        // now parse object and get separate all payloads from above array
                                        $finalpayload[] = $_finalpayload['data'][0];
                                    } elseif (isset($_finalpayload) && $_finalpayload['status'] == AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY) {
                                        Log::info($_finalpayload['data'][0]);
                                        if (isset($_finalpayload['data'][0]) && $_finalpayload['data'][0] == 'Delivery control applied.') {
                                            $delivery_error[] = 'Delivery control applied.';
                                            //return $this->addResponse(AppStatusCodes::HTTP_OK, 'success', ['Delivery control applied.'], 'data');
                                        } else {
                                            $payload_error[] = (isset($_finalpayload['data'][0])) ? $_finalpayload['data'][0] : $_finalpayload['data'];
                                            //return $this->addResponse(AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY, 'error', $_finalpayload['data'], 'error');
                                        }
                                    }
                                    //}
                                }
                            }

                            //dump($finalpayload);

                            if (isset($finalpayload) && count($finalpayload) > 0) {
                                for ($finalVal = 0; $finalVal < count($finalpayload); $finalVal++) {

                                    if (isset($finalpayload[$finalVal])) {
                                        // getting priority
                                        $priority = (isset($finalpayload[$finalVal]['payload']['data']['priority'])) ? $finalpayload[$finalVal]['payload']['data']['priority'] : Campaign::PRIORITY_MEDIUM;
                                        $campaign_type = $finalpayload[$finalVal]['payload']['data']['type'];
                                        $variant_name = "";
                                        $variant_code = (isset($finalpayload[$finalVal]['variant_code'])) ? $finalpayload[$finalVal]['variant_code'] : 'variant1';
                                        if ($variant_code != "variant1") {
                                            $variant_name = $variant_code;
                                        }
                                        if ($campaign_type == Campaign::CAMPAIGN_PUSH_CODE) {
                                            if (in_array($deviceToken, $finalpayload[$finalVal]['payload']['data'])) {
                                                \Queue::pushOn(Campaign::CAMPAIGN_PUSH_CODE . $priority . $variant_name,
                                                    new PushJobWorker($finalpayload[$finalVal]['payload']),
                                                    $finalpayload[$finalVal]['payload_interval']
                                                );
                                                Log::info("Device token matched in push worker.");
                                            }
                                        } else if ($campaign_type == Campaign::CAMPAIGN_INAPP_CODE) {
                                            if (in_array($deviceToken, $finalpayload[$finalVal]['payload']['data'])) {
                                                \Queue::pushOn(Campaign::CAMPAIGN_INAPP_CODE . $priority . $variant_name,
                                                    new PushJobWorker($finalpayload[$finalVal]['payload']),
                                                    $finalpayload[$finalVal]['payload_interval']);
                                                Log::info("Device token matched in inapp worker.");
                                            }
                                        } else {
                                            \Queue::pushOn(Campaign::CAMPAIGN_EMAIL_CODE . $priority . $variant_name,
                                                new EmailJobWorker($finalpayload[$finalVal]['payload']),
                                                $finalpayload[$finalVal]['payload_interval']
                                            );
                                        }
                                        if ($campaign_type == Campaign::CAMPAIGN_PUSH_CODE || $campaign_type == Campaign::CAMPAIGN_INAPP_CODE) {
                                            $tokens_data = (isset($finalpayload[$finalVal]['payload']['data']['tokens_data'])) ? $finalpayload[$finalVal]['payload']['data']['tokens_data'] : [];
                                            if (!empty($tokens_data)) {
                                                foreach ($tokens_data as $_token) {
                                                    $device_tokens = (isset($_token['device_token'])) ? $_token['device_token'] : [];
                                                    $tracking_keys = (isset($_token['tracking_key'])) ? $_token['tracking_key'] : [];
                                                    if (isset($tracking_keys)) {
                                                        $campaignTrackey = CommonHelper::getTrackkey($tracking_keys, $device_tokens);

                                                        if(count($data['action_array']) > 0){
                                                            foreach ($data['action_array'] as $key){
                                                                $finalPayload = array(
                                                                    'row_id' => $getUsersRowIds['row_id'],
                                                                    'campaign_id' => $finalpayload[$finalVal]['payload']['data']['id'],
                                                                    'campaign_code' => $finalpayload[$finalVal]['payload']['data']['code'],
                                                                    'track_key' => $campaignTrackey->track_key,
                                                                    'event_id' => $key['code'],
                                                                    'event_value' => $key['value'],
                                                                    'device_type' => $devicetype,
                                                                    'build' => $build,
                                                                    'version' => $version,
                                                                    'app_id' => $appId,
                                                                    'rec_type' => 'action_trigger'
                                                                );
                                                                AppUserActivity::create($finalPayload);
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        } else {
                                            if(count($data['action_array']) > 0) {
                                                foreach ($data['action_array'] as $key) {
                                                    $finalPayload = array(
                                                        'row_id' => $getUsersRowIds['row_id'],
                                                        'campaign_id' => $finalpayload[$finalVal]['payload']['data']['id'],
                                                        'campaign_code' => $finalpayload[$finalVal]['payload']['data']['code'],
                                                        'track_key' => $finalpayload[$finalVal]['payload']['data']['track_key'],
                                                        'event_id' => $key['code'],
                                                        'event_value' => $key['value'],
                                                        // 'device_type' => $devicetype,
                                                        'build' => $build,
                                                        'version' => $version,
                                                        'app_id' => $appId,
                                                        'rec_type' => 'action_trigger'
                                                    );
                                                    AppUserActivity::create($finalPayload);
                                                }
                                            }
                                        }
                                    }
                                }
                            } elseif (count($delivery_error) > 0) {
                                return $this->addResponse(AppStatusCodes::HTTP_OK, 'success', ['Delivery control applied.'], 'data');
                            } elseif (count($payload_error) > 0) {
                                return $this->addResponse(AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY, 'error', [$payload_error[0]], 'error');
                            } else {
                                throw new \Exception('User Does Not Exist.');
                            }
                        } else {
                            throw new \Exception('User Does Not Exist.');
                        }
//                                //*********************
//                                $row_ids = array();
//                                //dd($campaign_id);
//                                // get all segments from campaign segment cache
//                                $segments = [];
//                                $segmentRows = [];
//                                $_campaign = new CampaignSegmentCache();
//                                $segments = $_campaign->getCampaignSegmentsCache($campaignId[$val]['campaign_id']);
//                                if (!isset($segments)) {
//                                    throw new \Exception('No Campaign segment found.');
//                                    return false;
//                                }
//                                if (sizeof($segments) > 0) {
//                                    $segmentRows = new AppGroupSegmentCache();
//                                    foreach ($segments as $_segment) {
//                                        $_segment_rows = $segmentRows->getAppGroupSegmentRowsCache($appGroupId, $_segment);
//                                        if ($_segment_rows !== null && (isset($_segment_rows)) && sizeof($_segment_rows) > 0) {
//                                            foreach ($_segment_rows as $_row_id) {
//                                                $row_ids[] = $_row_id;
//                                            }
//                                        }
//                                    }
//                                }
                        //**************************
                        // 1. LOOP IN ALL VALID CAMPAIGNS --> line 443
                        // 2. Pass Campaign ID and GET CAMPAIGN SEGMENT ROWS IDS
                        // --- $returnedUsersArray = [1001, 1002, 1003] -- row ids
                        // 3. line 428 gets the ROW ID Record: $getUsersRowIds['row_id']
                        // --- condition will be if ($getUsersRowIds['row_id'] exists in $returnedUsersArray )
                        // that means user's row id matched with the campaign segment row id,
                        // 4. Only this campaign id passes to the campaign dispatcher method....
                        //}
                        //dump($userArray);
//                            if (in_array($getUsersRowIds['row_id'], $userArray)) {
//                                $payload_error=[];$delivery_error=[];
//                                foreach ($campaignId as $row) {
//                                    //dd($row['campaign_id'], $row);
//                                    $payload = CampaignDispatchProcess::processDispatcher([$getUsersRowIds['row_id']], $row['campaign_id']);
//                                    //dump($getUsersRowIds['row_id'], $row['campaign_id'], $payload);
//                                    $_finalpayload = \GuzzleHttp\json_decode($payload, true);
//
//                                    if (isset($_finalpayload) && $_finalpayload['status'] == AppStatusCodes::HTTP_OK) {
//                                        // now parse object and get separate all payloads from above array
//                                        $finalpayload[] = $_finalpayload['data'][0];
//                                    } elseif (isset($_finalpayload) && $_finalpayload['status'] == AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY) {
//                                        Log::info($_finalpayload['data'][0]);
//                                        if (isset($_finalpayload['data'][0]) && $_finalpayload['data'][0] == 'Delivery control applied.') {
//                                            $delivery_error[] = 'Delivery control applied.';
//                                            //return $this->addResponse(AppStatusCodes::HTTP_OK, 'success', ['Delivery control applied.'], 'data');
//                                        } else {
//                                            $payload_error[] = $_finalpayload['data'];
//                                            //return $this->addResponse(AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY, 'error', $_finalpayload['data'], 'error');
//                                        }
//                                    }
//                                }
//
//                                if (isset($finalpayload) && count($finalpayload) > 0) {
//                                    for ($finalVal = 0; $finalVal < count($finalpayload); $finalVal++) {
//
//                                        if (isset($finalpayload[$finalVal])) {
//                                            // getting priority
//                                            $priority = (isset($finalpayload[$finalVal]['payload']['data']['priority'])) ? $finalpayload[$finalVal]['payload']['data']['priority'] : Campaign::PRIORITY_MEDIUM;
//                                            $campaign_type = $finalpayload[$finalVal]['payload']['data']['campaign_type'];
//                                            $variant_name = "";
//                                            $variant_code = (isset($finalpayload[$finalVal]['variant_code'])) ? $finalpayload[$finalVal]['variant_code'] : 'variant1';
//                                            if ($variant_code != "variant1") {
//                                                $variant_name = $variant_code;
//                                            }
//                                            if ($campaign_type == Campaign::CAMPAIGN_PUSH_CODE) {
//                                                if (in_array($deviceToken, $finalpayload[$finalVal]['payload']['data'])) {
//                                                    \Queue::pushOn(Campaign::CAMPAIGN_PUSH_CODE . $priority . $variant_name,
//                                                        new PushJobWorker($finalpayload[$finalVal]['payload']),
//                                                        $finalpayload[$finalVal]['payload_interval']
//                                                    );
//                                                    Log::emergency("Device Token matched in push worker");
//                                                }
//                                            } else if ($campaign_type == Campaign::CAMPAIGN_INAPP_CODE) {
//                                                if (in_array($deviceToken, $finalpayload[$finalVal]['payload']['data'])) {
//                                                    \Queue::pushOn(Campaign::CAMPAIGN_INAPP_CODE . $priority . $variant_name,
//                                                        new PushJobWorker($finalpayload[$finalVal]['payload']),
//                                                        $finalpayload[$finalVal]['payload_interval']);
//                                                    Log::emergency("Device Token matched in Inapp worker");
//                                                }
//                                            } else {
//                                                \Queue::pushOn(Campaign::CAMPAIGN_EMAIL_CODE . $priority . $variant_name,
//                                                    new EmailJobWorker($finalpayload[$finalVal]['payload']),
//                                                    $finalpayload[$finalVal]['payload_interval']
//                                                );
//                                            }
//                                            if ($campaign_type == Campaign::CAMPAIGN_PUSH_CODE || $campaign_type == Campaign::CAMPAIGN_INAPP_CODE) {
//                                                $tokens_data = (isset($finalpayload[$finalVal]['payload']['data']['tokens_data'])) ? $finalpayload[$finalVal]['payload']['data']['tokens_data'] : [];
//                                                if (!empty($tokens_data)) {
//                                                    foreach ($tokens_data as $_token) {
//                                                        $device_tokens = (isset($_token['device_token'])) ? $_token['device_token'] : [];
//                                                        $tracking_keys = (isset($_token['tracking_key'])) ? $_token['tracking_key'] : [];
//                                                        if (isset($tracking_keys)) {
//                                                            $campaignTrackey = CommonHelper::getTrackkey($tracking_keys, $device_tokens);
//                                                            $finalPayload = array(
//                                                                'row_id' => $getUsersRowIds['row_id'],
//                                                                'campaign_id' => $finalpayload[$finalVal]['payload']['data']['campaign_id'],
//                                                                'campaign_code' => $finalpayload[$finalVal]['payload']['data']['campaign_code'],
//                                                                'track_key' => $campaignTrackey->track_key,
//                                                                'event_id' => $data['code'],
//                                                                'event_value' => $data['value'],
//                                                                'device_type' => $devicetype,
//                                                                'build' => $build,
//                                                                'version' => $version,
//                                                                'app_id' => $appId,
//                                                                'rec_type' => 'action_trigger'
//                                                            );
//                                                            AppUserActivity::create($finalPayload);
//                                                        }
//                                                    }
//                                                }
//                                            } else {
//                                                $finalPayload = array(
//                                                    'row_id' => $getUsersRowIds['row_id'],
//                                                    'campaign_id' => $finalpayload[$finalVal]['payload']['data']['campaign_id'],
//                                                    'campaign_code' => $finalpayload[$finalVal]['payload']['data']['campaign_code'],
//                                                    'track_key' => $finalpayload[$finalVal]['payload']['data']['track_key'],
//                                                    'event_id' => $data['code'],
//                                                    'event_value' => $data['value'],
//                                                    // 'device_type' => $devicetype,
//                                                    'build' => $build,
//                                                    'version' => $version,
//                                                    'app_id' => $appId,
//                                                    'rec_type' => 'action_trigger'
//                                                );
//                                                AppUserActivity::create($finalPayload);
//                                            }
//                                        }
//                                    }
//                                }
//                                elseif(count($delivery_error) > 0){
//                                    return $this->addResponse(AppStatusCodes::HTTP_OK, 'success', ['Delivery control applied.'], 'data');
//                                }
//                                elseif($payload_error[0] > 0){
//                                    return $this->addResponse(AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY, 'error', $payload_error[0], 'error');
//                                }
//                                else {
//                                    throw new \Exception('User Does Not Exist.');
//                                }
//                            } else {
//                                throw new \Exception('User Does Not Exist.');
//                            }
                        //}
                    //}
                }
            }
            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                ['Action trigger campaign has been send.'],
                'data'
            );
        } catch (\Exception $exception) {
            return $this->addResponse(AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY, 'error', [$exception->getMessage()], 'error'); // ->getMessage()
        }
    }

    /*
    * Campaign Users from cache
    *
    */

    public function getCampaignUsers($campaign_id, $app_group_id)
    {
        $row_ids = array();
        //dd($campaign_id);
        // get all segments from campaign segment cache
        $segments = [];
        $segmentRows = [];
        $_campaign = new CampaignSegmentCache();
        $segments = $_campaign->getCampaignSegmentsCache($campaign_id);
        if (!isset($segments)) {
            throw new \Exception('No Campaign segment found.');
            return false;
        }
        if (sizeof($segments) > 0) {
            $segmentRows = new AppGroupSegmentCache();
            foreach ($segments as $_segment) {
                $_segment_rows = $segmentRows->getAppGroupSegmentRowsCache($app_group_id, $_segment);
                if ($_segment_rows !== null && (isset($_segment_rows)) && sizeof($_segment_rows) > 0) {
                    foreach ($_segment_rows as $_row_id) {
                        $row_ids[] = $_row_id;
                    }
                }
            }
        }
        return $row_ids;
    }

    /*
    * Campaign api Trigger
    *
    */
    public function apiTrigger(Request $request)
    {
        try {
            $user = $request->user();
            $companyid = $user->id;
            $data = $this->parseResponse($request);
            $appId = $request->header('app-id');
            $appName = $request->header('app-name');
            $devicetype = $request->header('device-type');
            $version = $request->header('app-version');
            $build = $request->header('app-build');
            $validator = new CampaignInAppTriggerValidatorRequest($data);
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
                'user_id' => $data['user_id'],
                'device_type' => $devicetype,
                'appName' => $appName,
                'company_id' => $companyid
            );
            $campaignId = DB::table('campaign')->where('code', $data['campaign_code'])->first();
            if (!$campaignId) {
                throw new \Exception('Campaign Does Not Exist.');
            }
            $appGroupId = (new CampaignValidation())->getAppGroupId($userParam);
            if ($appGroupId) {
                $data['app_group_id'] = $appGroupId->app_group_id;
                $data['company_id'] = $appGroupId->company_id;
                $userParam['company_id'] = $appGroupId->company_id;
                $getUsersRowIds = (new CommonHelper())->getRowIds($userParam);
                if ($getUsersRowIds) {
                    $payload = CampaignDispatchProcess::processDispatcher([$getUsersRowIds['row_id']], $campaignId->id);
                    $finalpayload = \GuzzleHttp\json_decode($payload, true);

                    if (isset($finalpayload) && $finalpayload['status'] == AppStatusCodes::HTTP_OK) {
                        // now parse object and get separate all payloads from above array
                        $finalpayload = $finalpayload['data'];
                    } elseif (isset($finalpayload) && $finalpayload['status'] == AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY) {
                        Log::info($finalpayload['data']);
                        return $this->addResponse(AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY, 'error', $finalpayload['data'], 'error');
                    }
                    for ($finalVal = 0; $finalVal < count($finalpayload); $finalVal++) {
                        // getting priority
                        $priority = (isset($finalpayload[$finalVal]['payload']['data']['priority'])) ? $finalpayload[$finalVal]['payload']['data']['priority'] : Campaign::PRIORITY_MEDIUM;
                        $campaign_type = $finalpayload[$finalVal]['payload']['data']['type'];
                        $variant_name = "";
                        $variant_code = (isset($finalpayload[$finalVal]['variant_code'])) ? $finalpayload[$finalVal]['variant_code'] : 'variant1';
                        if ($variant_code != "variant1") {
                            $variant_name = $variant_code;
                        }

                        if ($finalpayload[$finalVal]['payload']['data']['type'] == Campaign::CAMPAIGN_PUSH_CODE) {
                            \Queue::pushOn(Campaign::CAMPAIGN_PUSH_CODE . $priority . $variant_name,
                                new PushJobWorker($finalpayload[$finalVal]['payload']),
                                $finalpayload[$finalVal]['payload_interval']
                            );
                        } else if ($finalpayload[$finalVal]['payload']['data']['type'] == Campaign::CAMPAIGN_INAPP_CODE) {
                            \Queue::pushOn(Campaign::CAMPAIGN_INAPP_CODE . $priority . $variant_name,
                                new PushJobWorker($finalpayload[$finalVal]['payload']),
                                $finalpayload[$finalVal]['payload_interval']);
                        } else {
                            \Queue::pushOn(Campaign::CAMPAIGN_EMAIL_CODE . $priority . $variant_name,
                                new EmailJobWorker($finalpayload[$finalVal]['payload']),
                                $finalpayload[$finalVal]['payload_interval']
                            );
                        }
                        if (!empty($data['extra_params'])) {
                            $eventId = 'extra_params';
                            $eventValue = json_encode($data['extra_params']);
                        } else {
                            $eventId = 'extra_params';
                            $eventValue = '';
                        }
                        if ($campaign_type == Campaign::CAMPAIGN_PUSH_CODE || $campaign_type == Campaign::CAMPAIGN_INAPP_CODE) {
                            $tokens_data = (isset($finalpayload[$finalVal]['payload']['data']['tokens_data'])) ? $finalpayload[$finalVal]['payload']['data']['tokens_data'] : [];
                            // dd($tokens_data);
                            if (!empty($tokens_data)) {
                                foreach ($tokens_data as $_token) {
                                    $device_tokens = (isset($_token['device_token'])) ? $_token['device_token'] : [];
                                    $tracking_keys = (isset($_token['tracking_key'])) ? $_token['tracking_key'] : [];
                                    if (isset($tracking_keys)) {
                                        $campaignTrackey = CommonHelper::getTrackkey($tracking_keys, $device_tokens);
                                        $finalPayload = array(
                                            'row_id' => $getUsersRowIds['row_id'],
                                            'campaign_id' => $finalpayload[$finalVal]['payload']['data']['id'],
                                            'campaign_code' => $finalpayload[$finalVal]['payload']['data']['code'],
                                            'track_key' => $campaignTrackey->track_key,
                                            'event_id' => $eventId,
                                            'event_value' => $eventValue,
                                            'device_type' => $devicetype,
                                            'build' => $build,
                                            'version' => $version,
                                            'app_id' => $appId,
                                            'rec_type' => 'api_trigger'
                                        );
                                        AppUserActivity::create($finalPayload);
                                    }
                                }
                            }
                        } else {
                            $finalPayload = array(
                                'row_id' => $getUsersRowIds['row_id'],
                                'campaign_id' => $finalpayload[$finalVal]['payload']['data']['id'],
                                'campaign_code' => $finalpayload[$finalVal]['payload']['data']['code'],
                                'track_key' => $finalpayload[$finalVal]['payload']['data']['track_key'],
                                'event_id' => $eventId,
                                'event_value' => $eventValue,
                                'device_type' => $devicetype,
                                'build' => $build,
                                'version' => $version,
                                'app_id' => $appId,
                                'rec_type' => 'api_trigger'
                            );
                            AppUserActivity::create($finalPayload);
                        }
                    }
                    return $this->addResponse(
                        AppStatusCodes::HTTP_OK,
                        'success',
                        ['Api trigger campaign has been send.'],
                        'data'
                    );
                }
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

    /*
    * Campaign Conversion Trigger
    *
    */
    public function conversionTrigger(Request $request)
    {
        try {
            $todayDate = Carbon::now()->format('Y-m-d h:i:s');
            $data = $this->parseResponse($request);
            $appId = $request->header('app-id');
            $appName = $request->header('app-name');
            $devicetype = $request->header('device-type');
            $version = $request->header('app-version');
            $build = $request->header('app-build');
            $deviceToken = $request->header('device-token');
            $validator = new CampaignConversionTriggerValidatorRequest($data);
            if (!empty($validator->errors)) {
                return $this->addResponse(
                    AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                    'error',
                    $validator->errors,
                    'error'
                );
            }
            $campaignTrackey = CommonHelper::getTrackkey($data['track_key'], $deviceToken);
            if ($campaignTrackey) {
                $campaignId = $campaignTrackey->campaign_id;
                $campaign_code = $campaignTrackey->code;
                $campaignActionlist = CampaignAction::where('value', $data['value'])
                    ->where('campaign_id', '=', $campaignId)
                    ->where('action_type', 'conversion')->first();
                if ($campaignActionlist) {
                    $period = $campaignActionlist->period;
                    $validity = $campaignActionlist->validity;
                    $actionId = $campaignActionlist->action_id;
                    $datetime = new Carbon($campaignTrackey->sent_at);
                    $seconds = self::getSeconds($validity, $period);
                    $datetime->addSeconds($seconds)->format('Y-m-d h:i:s');
                    if ($datetime <= $todayDate) {
                        throw new \Exception('Conversion Validity period expired');
                    } else {
                        $finalPayload = array(
                            'row_id' => $campaignTrackey['row_id'],
                            'campaign_id' => $campaignId,
                            'campaign_code' => $campaign_code,
                            'track_key' => $campaignTrackey->track_key,
                            'event_id' => $data['code'],
                            'event_value' => $data['value'],
                            'device_type' => $devicetype,
                            'build' => $build,
                            'version' => $version,
                            'app_id' => $appId,
                            'rec_type' => 'conversion'
                        );
                        $saveAppUserActivity = AppUserActivity::create($finalPayload);
                        if ($saveAppUserActivity) {
                            return $this->addResponse(
                                AppStatusCodes::HTTP_OK,
                                'success',
                                ['Campaign conversion has been saved.'],
                                'data'
                            );
                        } else {
                            throw new \Exception('Failed app user activity not saved.');
                        }
                    }
                } else {
                    throw new \Exception('Campaign Action Does Not Exist.');
                }
            }
        } catch (\Exception $exception) {
            return $this->addResponse(AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY, 'error', [$exception->getMessage()], 'error');
        }
    }

    /*
   * Get seconds
   *
   */
    public static function getSeconds($value, $unit)
    {
        $timeUnit = [
            "minute" => 60,
            "hour" => 3600,
            "day" => 86400,
        ];
        $seconds = $value * $timeUnit[$unit];
        return $seconds;
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
            $campaignTracking = (new CampaignValidation())->campaignTrackkeyValidator($data);
            if ($campaignTracking) {
                $campaignType = Campaign::where('id', $campaignTracking->campaign_id)->first();
                $linkTrackingObj = array(
                    'rec_type' => $campaignType->campaign_type,
                    'rec_id' => $campaignTracking->campaign_id,
                    'row_id' => $campaignTracking->row_id,
                    'campaign_code' => $campaignTracking->code,
                    'actual_url' => (isset($data['action_url'])) ? $data['action_url'] : "",
                    'created_date' => Carbon::now(),
                    'device_type' => $devicetype,
                    'ip_address' => '',
                    'user_agent' => '',
                    'viewed' => '1',
                    'campaign_tracking_viewed' => $campaignTracking->viewed,
                    'track_key' => $data['track_key'],
                    'device_token' => $data['device_token']
                );
                switch ($mode) {
                    case 'viewed':
                        $response = (new CampaignValidation())->updateCampaignTrackingStatus($campaignTracking->viewed, $data);
                        $response = ['Tracking has been saved.'];
                        break;
                    case 'clicked':
                        $response = (new CampaignValidation())->insertLinkTracking($mode, $linkTrackingObj);
                        break;
                    case 'both':
                        $response = (new CampaignValidation())->insertLinkTracking($mode, $linkTrackingObj);
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

    public function campaignQueuesListing(Request $request)
    {
        try {

            $queryChain = CampaignQueue::join('campaign', 'campaign.id', '=', 'campaign_queue.campaign_id')
                ->leftjoin('app_group', 'app_group.id', '=', 'campaign.app_group_id')
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
                    $query->where('campaign_queue.id', 'LIKE', "%{$search}%");
                    $query->orWhere('campaign_queue.status', 'LIKE', "%{$search}%");
                    $query->orWhere('campaign_queue.details', 'LIKE', "%{$search}%");
                    $query->orWhere('campaign_queue.error_message', 'LIKE', "%{$search}%");
                    $query->orWhere('campaign_queue.created_at', 'LIKE', "%{$search}%");
                    $query->orWhere('users.name', 'LIKE', "%{$search}%");
                });
            }
            $totalFiltered = clone $queryChain;
            $totalFiltered = $totalFiltered->count();
            isset($request["orderBy"]) ? $queryChain->orderBy($request["orderBy"], $request["ascending"] == 1 ? 'desc' : 'asc') : $queryChain->orderBy('updated_at', 'desc');
            $data = $queryChain->offset(($request['page'] - 1) * $request['limit'])
                ->limit($request['limit'])
                ->get(['users.name as company_name', 'campaign_queue.*']);
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

    public function updateCampaignQueueStatus(Request $request)
    {
        try {
            $attr = $request->all();
            $queue = CampaignQueue::find($attr['id']);

            if (empty($queue)) {
                return $this->addResponse(
                    AppStatusCodes::HTTP_NOT_FOUND,
                    'error',
                    ['Campaign queue not found.'],
                    'error'
                );
            }

            if (isset($attr['status']) AND $attr['status'] == 'Available') {
                $queue->update([
                    'status' => $attr['status']
                ]);
            }

            if (isset($attr['status']) AND $attr['status'] == 'Processing') {
                Artisan::call('backend:campaign:queue', [
                    'id' => $attr['id']
                ]);
            }

            return response()->json('Status updated successfully.');
        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_NOT_FOUND,
                'error',
                ['Unable to load campaign queues data'],
                $exception->getMessage()
            );
        }
    }

    public function saveCappingSettings($data)
    {
        try {

            $appGroupId = \Request::user()->currentAppGroup()->id;
            CampaignCapRule::where("app_group_id", $appGroupId)->delete();
            foreach ($data as $capRule) {
                $rule = new CampaignCapRule();
                $rule->app_group_id = $appGroupId;
                $rule->cap_limit = $capRule["capLimit"];
                $rule->campaign_type = $capRule["campaignType"];
                $rule->duration_unit = $capRule["durationUnit"];
                $rule->duration_value = $capRule["durationValue"];
                $rule->save();
            }

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                [],
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

    public function getCappingSettings()
    {
        try {

            $appGroupId = \Request::user()->currentAppGroup()->id;

            $campaignRules = CampaignCapRule::where("app_group_id", $appGroupId)
                ->select("cap_limit as capLimit", "campaign_type as campaignType", "duration_value as durationValue", "duration_unit as durationUnit")
                ->get();

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $campaignRules,
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

    public function resendNotification(Request $request)
    {

        try {
            $message = '';
            $data = $this->parseResponse($request);
            $campaignTracking = CampaignTracking::where('id', $data['id'])->first();
            if ($campaignTracking) {
                $campaingId = $campaignTracking->campaign_id;
                $row_id = $campaignTracking->row_id;
                $app_user_token_id = $campaignTracking->app_user_token_id;
                $email = $campaignTracking->email;
                $server_key = $campaignTracking->firebase_key;
                $device_key = $campaignTracking->device_key;
                $payload = \GuzzleHttp\json_decode($campaignTracking->payload, true);
                $track_key = $campaignTracking->track_key;
                $device_type = $campaignTracking->device_type;
                $job = $campaignTracking->job;
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
                            $message = 'Notification Send Successfully';
                            $trackingLog = array(
                                'campaign_tracking_id' => $data['id'],
                                'status' => 'completed',
                                'message' => 'Notification Send Successfully'
                            );
                            $trackingdata = array(
                                'id' => $data['id'],
                                'status' => 'completed'
                            );
                            $this->updateTrackingStatus($trackingdata);
                            $this->savetrackinglog($trackingLog);
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
                            'campaign_tracking_id' => $data['id'],
                            'status' => 'completed',
                            'message' => 'Email Send Successfully'
                        );
                        $trackingdata = array(
                            'id' => $data['id'],
                            'status' => 'completed'
                        );
                        $message = 'Email Send Successfully';
                        $this->savetrackinglog($trackingLog);
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
                throw new \Exception('Failed Not Valid Campaign');
            }
        } catch (\Exception $exception) {
            $tracking = array(
                'campaign_tracking_id' => $request['id'],
                'status' => 'failed',
                'message' => $exception->getMessage()
            );
            $trackingdata = array(
                'id' => $request['id'],
                'status' => 'failed'
            );
            $this->updateTrackingStatus($trackingdata);
            $this->savetrackinglog($tracking);
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
        $result = CampaignTracking::where('id', $data['id'])->update([
            'status' => $data['status']
        ]);
        return $result;
    }

    public function savetrackinglog($data)
    {
        $result = CampaignTrackingLog::create($data);
        return $result;
    }

    public function userActionList(\Illuminate\Http\Request $request)
    {
        try {
            $user = $request->user();
            $companyid = $user->id;
            $userArray = array();
            $data = $this->parseResponse($request);
            $dataType = $data['data_type'];
            $appId = $request->header('app-id');
            $appName = $request->header('app-name');
            $devicetype = $request->header('device-type');
            $version = $request->header('app-version');
            $build = $request->header('app-build');
            $validator = new ActionListingValidator($data);
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
                'user_id' => $data['user_id'],
                'company_id' => $companyid
            );
            $getUsersRowIds = (new CommonHelper())->getRowIds($userParam);
            if ($getUsersRowIds) {
                $rowId = $getUsersRowIds->row_id;
                if ($dataType == 'action_trigger') {
                    $dataType = 'action';
                } else {
                    $dataType = 'conversion';
                }
                switch ($dataType) {
                    case 'action':
                        $response = $this->getUserActions('action_trigger', $rowId);
                        break;
                    case 'conversion':
                        $response = $this->getUserActions('conversion', $rowId);
                }
                return $this->addResponse(
                    AppStatusCodes::HTTP_OK,
                    AppStatusMessages::SUCCESS,
                    $response,
                    'data'
                );
            }
        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_NOT_FOUND,
                'error',
                ['Unable to load user action list data'],
                $exception
            );
        }

    }

    public function getUserActions($dataType, $rowId)
    {
        $conversion = array();
        $action_trigger = array();
        $finalResponse = array();
        $response = AppUserActivity::where('row_id', '=', $rowId)
            ->where('rec_type', '=', $dataType)->get(['event_id as code', 'event_value as value', 'rec_type as attribute_type']);
        if (count($response) > 0) {
            for ($val = 0; $val < count($response); $val++) {
                if ($response[$val]['rec_type'] == "conversion") {
                    $conversion[] = $response[$val];
                } else if ($response[$val]['rec_type'] == "action_trigger") {
                    $action_trigger[] = $response[$val];
                } else {
                    $action_trigger = [];
                    $conversion = [];
                }
            }
        }
        $finalResponse = array(
            'action_trigger' => $action_trigger,
            'conversion_trigger' => $conversion
        );
        return $finalResponse;
    }
}