<?php

namespace App\Jobs;

use App\Board;
use App\BoardTracking;
use App\BoardVariant;
use App\Cache\BoardUserTrackingCache;
use App\Campaign;
use App\CampaignQueue;
use App\Helpers\BoardValidation;
use http\Env\Response;
use Illuminate\Http\Request;
use App\Cache\CampaignTrackingCache;
use App\CampaignTracking;
use App\CampaignTrackingLog;
use App\Components\AppPlatforms;
use App\Components\AppStatusCodes;
use App\Components\CampaignCappingControl;
use App\Components\CampaignWorkerPayload;
use App\Components\InteractsWithMessages;
use App\Http\Controllers\NotificationController;
use App\Helpers\CommonHelper;
use App\Components\CampaignQueueComponent;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Helpers\CampaignValidation;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PushJobWorker implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, InteractsWithMessages;

    /**
     * @Document Payload received for Push
     */
    private $payload;

    /**
     * PushJobWorker constructor.
     *
     * @param $payload
     */
    public function __construct($payload)
    {
        $this->payload = $payload;
    }

    /**
     * Execute the push, inapp jobs.
     *
     * @return Response
     */
    public function handle()
    {
        Log::info('Job started: ' . Carbon::now()->format('Y-m-d h:i:s'));
        $data = (isset($this->payload[0])) ? $this->payload[0] : $this->payload;

        if( ($data['data']['is_board']) && (bool)$data['data']['is_board'] !== false){
            $campaign_type = (isset($data['data']['type'])) ? strtolower($data['data']['type']) : strtolower(BoardVariant::VARIANT_PUSH_CODE);
            if (strtolower($campaign_type) == strtolower(BoardVariant::VARIANT_PUSH_CODE) || strtolower($campaign_type) == strtolower(BoardVariant::VARIANT_INAPP_CODE) ) {
                $result = $this->processBoardJob($data);
                Log::info('Board completed: ' . Carbon::now()->format('Y-m-d h:i:s'));
            }
            else{
                Log::info('Board failed: ' . Carbon::now()->format('Y-m-d h:i:s'));
                throw new \Exception("Failed, Board variant is not " . $campaign_type . " type.");
            }
        }
        else{
            $campaign_type = (isset($data['data']['type'])) ? strtolower($data['data']['type']) : strtolower(Campaign::CAMPAIGN_PUSH_CODE);
            if ($campaign_type == strtolower(Campaign::CAMPAIGN_PUSH_CODE) || $campaign_type == strtolower(Campaign::CAMPAIGN_INAPP_CODE)) {
                $result = $this->processCampaignJob($data);
                Log::info('Campaign completed: ' . Carbon::now()->format('Y-m-d h:i:s'));
            }
            else{
                Log::info('Campaign failed: ' . Carbon::now()->format('Y-m-d h:i:s'));
                throw new \Exception("Failed, Campaign is not " . $campaign_type . " type.");
            }
        }
    }

    public function processCampaignJob($data)
    {
        // prepare and parse required params
        $app_group_id = (isset($data['data']['app_group_id'])) ? $data['data']['app_group_id'] : "";
        $campaign_id = (isset($data['data']['id'])) ? $data['data']['id'] : "";
        $row_id = (isset($data['data']['row_id'])) ? $data['data']['row_id'] : "";
        $tokens_data = (isset($data['data']['tokens_data'])) ? $data['data']['tokens_data'] : "";
        $language = (isset($data['data']['language'])) ? $data['data']['language'] : "";
        $variant_id = (isset($data['data']['variant_id'])) ? $data['data']['variant_id'] : 1;

        $response = array();
        try {

            // apply campaign validation checks
            $validation = CampaignValidation::validation($campaign_id);

            $_tracking_keys = [];
            $device_tokens = [];
            $server_key = '';
            $campaign_tracking_id = [];
            $device_type = strtolower(AppPlatforms::PLATFORM_ANDROID);
            foreach ($tokens_data as $key => $value) {

                $device_type = strtolower($value['device_type']);
                if ($device_type == strtolower(AppPlatforms::PLATFORM_ANDROID)) {
                    $server_key = $value['server_key'];
                } elseif ($device_type == strtolower(AppPlatforms::PLATFORM_IOS)) {
                    $server_key = $value['server_key'];
                } elseif ($device_type == strtolower(AppPlatforms::PLATFORM_WEB)) {
                    $server_key = $value['server_key'];
                } else {
                    $server_key = $value['server_key'];
                }

                foreach ($value['tracking_key'] as $key => $tracking) {
                    $trackingKey = $tracking;
                    $_tracking_keys[] = $tracking;
                }
                foreach ($value['device_token'] as $key => $_tokens) {
                    $device_tokens[] = $_tokens;
                }
            }
            $data['data']['track_keys'] = $_tracking_keys;

            $_params = [
                "title" => (isset($data['notification']['title'])) ? $data['notification']['title'] : "",
                "body" => (isset($data['notification']['body'])) ? $data['notification']['body'] : "",
                "link" => (isset($data['data']['action_value'])) ? $data['data']['action_value'] : "",
                "backgroundColor" => (isset($data['data']['backgroundColor'])) ? $data['data']['backgroundColor'] : "#FFFFFF",
                "device_type" => (isset($tokens_data['device_type'])) ? $tokens_data['device_type'] : $device_type, // remove later on
                "device_token" => '', // remove later on
                "app_group_id" => $app_group_id,
                "code" => (isset($data['data']['code'])) ? $data['data']['code'] : "",
                "user_id" => (isset($data['data']['user_id'])) ? $data['data']['user_id'] : "",
                "track_keys" => (isset($data['data']['track_keys'])) ? $data['data']['track_keys'] : [],
                "action_url" => (isset($data['data']['action_url'])) ? $data['data']['action_url'] : "",
                "is_hermis_platform" => $data['data']['is_hermis_platform'],
                "is_silent" => $data['data']['is_silent'],
                "type" => $data['data']['type'],
                "message_position" => $data['data']['message_position'],
                "message_type" => (isset($data['data']['message_type'])) ? $data['data']['message_type'] : "",
                "priority" => $data['data']['priority'],
                "icon" => ($data['data']['icon'] != "") ? $data['data']['icon'] : "",
                "auto_close" => $data['data']['auto_close'],
                "action_type" => (isset($data['data']['action_type'])) ? $data['data']['action_type'] : "deep link",
                "action_value" => (isset($data['data']['action_value'])) ? $data['data']['action_value'] : "",
                "dispatch_date" => (isset($data['data']['dispatch_date'])) ? $data['data']['dispatch_date'] : "",
                "view_link" => $data['data']['view_link'],
                "is_board" => false
            ];

            $content_available = 0;
            if ((bool)$_params['is_silent'] == true) {
                $content_available = 1;
            }

            if ($_params['type'] == Campaign::CAMPAIGN_PUSH_CODE) { // && strtolower($_params['device_type']) == AppPlatforms::PLATFORM_IOS

                // generating notification payload for push campaigns
                $notification = CampaignWorkerPayload::generatePayloadNotification($_params);
            } else {
                $notification = null;
            }
            //Log::info('Worker payload notify: ' . \GuzzleHttp\json_encode($notification));

            $payload_data = CampaignWorkerPayload::generatePayloadData($_params);
            //Log::info('Worker payload data: ' . \GuzzleHttp\json_encode($payload_data));

            /*\Artisan::call('config:cache');
            \Config::set('fcm.http.server_key', $server_key);*/

            if (isset($device_tokens) && count($device_tokens) > 0) {

                $notifications = new NotificationController($device_tokens, $notification, $payload_data, $server_key, $content_available);
                $response = $notifications->sendNotification();
                Log::info('campaign-response: ' . \GuzzleHttp\json_encode($response, true));
                if (isset($response) && count($response) > 0) {

                    // prepare and parse response params
                    $number_success = (isset($response['numberSuccess'])) ? $response['numberSuccess'] : 0;
                    $numberFailure = (isset($response['numberFailure'])) ? $response['numberFailure'] : 0;
                    $tokensToDelete = (isset($response['tokensToDelete'])) ? $response['tokensToDelete'] : [];
                    $tokensWithError = (isset($response['tokensWithError'])) ? $response['tokensWithError'] : [];

                    $is_sent = true;
                    if (!empty($data['data']['track_keys'])) {
                        foreach ($data['data']['track_keys'] as $_track_key) {

                            // preparing device key
                            $_device_key = (isset($data['data']['tokens_data'][0]['device_token'][0])) ? $data['data']['tokens_data'][0]['device_token'][0] : "";

                            if ((int)$number_success > 0) {

                                //Log::info('Notification send with status: ' . AppStatusCodes::HTTP_OK);
                                $success_tokens = [];
                                $success_tokens = array_diff($device_tokens, $tokensToDelete);

                                if (in_array($_device_key, $success_tokens)) {

                                    // updating campaign tracking with status
                                    DB::update(" Update campaign_tracking SET status='".Campaign::CAMPAIGN_TRACKING_COMPLETED_STATUS."',
                                        message='Notification sent successfully.', sent=1, sent_at='".Carbon::now()->format('Y-m-d h:i:s')."',
                                        ended_at='".Carbon::now()->format('Y-m-d h:i:s')."'
                                        where track_key = '".$_track_key."' ");
                                }
                            }
                            if (isset($tokensToDelete)) {

                                // updating device token revoked status
                                $result = CommonHelper::updateDeviceToken($tokensToDelete, [
                                    "app_group_id" => $app_group_id,
                                    'is_revoked' => '1',
                                    'status' => '0',
                                    'deleted_at' => Carbon::now()->format('Y-m-d h:i:s')
                                ]);

                                if (in_array($_device_key, $tokensToDelete)) {

                                    // updating campaign tracking with status
                                    DB::update("Update campaign_tracking SET status='" . Campaign::CAMPAIGN_TRACKING_FAILED_STATUS . "',
                                                message='Notification sending failed due to expire token(s).', sent_at='" . Carbon::now()->format('Y-m-d h:i:s') . "',
                                                ended_at='" . Carbon::now()->format('Y-m-d h:i:s') . "', payload='". \GuzzleHttp\json_encode($this->payload, true) ."'
                                                where track_key = '" . $_track_key . "'");
                                }
                            }
                            if ((int)$numberFailure == count($device_tokens) && !empty($tokensWithError)) {

                                $notification_error = "";
                                if (in_array($_device_key, $device_tokens)) {

                                    $error_token = (isset($tokensWithError[$_device_key])) ? $tokensWithError[$_device_key] : "";
                                    if ($error_token == "MessageTooBig") {
                                        $notification_error = "`MessageTooBig`, Payload size is too big.";
                                    } elseif ($error_token == "InvalidApnsCredential") {
                                        $notification_error = "`InvalidApnsCredential`, expire token(s).";
                                    } else {
                                        $notification_error = "expire token(s).";
                                    }

                                    // updating campaign tracking with status
                                    $message = 'Notification sending failed due to '.$notification_error;
                                    DB::update("Update campaign_tracking SET status='" . Campaign::CAMPAIGN_TRACKING_FAILED_STATUS . "',
                                                        message='". $message."', sent_at='" . Carbon::now()->format('Y-m-d h:i:s') . "',
                                                        ended_at='" . Carbon::now()->format('Y-m-d h:i:s') . "',
                                                        payload='". \GuzzleHttp\json_encode($this->payload, true) ."'
                                                        where track_key = '" . $_track_key . "'");
                                }
                                $is_sent = false;
                            }
                            if ((int)$numberFailure == count($device_tokens) && empty($tokensWithError)) {

                                $notification_error = "expired token(s).";
                                if (in_array($_device_key, $device_tokens)) {

                                    // updating campaign tracking with status
                                    DB::update("Update campaign_tracking SET  status='" . Campaign::CAMPAIGN_TRACKING_FAILED_STATUS . "',
                                        message='Notification sending failed due to expired token(s).', sent_at='" . Carbon::now()->format('Y-m-d h:i:s') . "',
                                        ended_at='" . Carbon::now()->format('Y-m-d h:i:s') . "',
                                        payload='". \GuzzleHttp\json_encode($this->payload, true) ."'
                                        where track_key = '" . $_track_key . "'");
                                }
                                $is_sent = false;
                            }
                            else{
                                $is_sent = false;
                            }

                            // update tracking cache
                            $last_sent_date = Carbon::now()->toDateTimeString();
                            $sent_count = 1;
                            $_tracking = new CampaignTrackingCache();
                            $content = (isset($data['notification']['body'])) ? $data['notification']['body'] : "";
                            $_tracking->updateCampaignTrackingCache($campaign_id, $row_id, $language, $variant_id, $content, $last_sent_date, $sent_count);

                            //$campaign = Campaign::find($campaign_id);
                            //CampaignCappingControl::setCappingInfo($campaign, $row_id, $language, $variant_id, $_tracking_sent_at);
                        }
                    }
                    else{
                        $is_sent = false;
                    }
                    if (!$is_sent) {
                        $response = [
                            'status' => 'failed',
                            'message' => 'Notification sending failed.'
                        ];
                    } else {
                        $response = [
                            'status' => 'success',
                            'message' => 'Notification sent successfully.'
                        ];
                    }
                }
            }
            return $response;
        } catch (\Exception $exception) {

            $track_keys = (isset($data['tokens_data']['tracking_key'])) ? $data['tokens_data']['tracking_key'] : [];
            if (!empty($track_keys)) {
                foreach ($track_keys as $_track_key) {

                    // updating campaign tracking with status
                    DB::update("Update campaign_tracking SET status='" . Campaign::CAMPAIGN_TRACKING_FAILED_STATUS . "',
                                        message= '".$exception->getMessage()."', sent_at='" . Carbon::now()->format('Y-m-d h:i:s') . "',
                                        ended_at='" . Carbon::now()->format('Y-m-d h:i:s') . "', payload='". \GuzzleHttp\json_encode($this->payload, true) ."'
                                        where track_key = '" . $_track_key . "'");

                    // update tracking cache
                    $last_sent_date = Carbon::now()->toDateTimeString();
                    $sent_count = 1;
                    $_tracking = new CampaignTrackingCache();
                    $content = (isset($data['notification']['body'])) ? $data['notification']['body'] : "";
                    $_tracking->updateCampaignTrackingCache($campaign_id, $row_id, $language, $variant_id, $content, $last_sent_date, $sent_count);
                }
            }
            $response = [
                'status' => 'error',
                'message' => $exception->getMessage()
            ];
            //\Artisan::call('config:clear');
            Log::error('Campaign Failed Exception: '. $exception->getMessage());

            return $response;
        }
    }

    public function processBoardJob($data)
    {
        // preparse and parse required params
        $app_group_id = (isset($data['data']['app_group_id'])) ? $data['data']['app_group_id'] : "";
        $board_id = (isset($data['data']['id'])) ? $data['data']['id'] : "";
        $row_id = (isset($data['data']['row_id'])) ? $data['data']['row_id'] : "";
        $tokens_data = (isset($data['data']['tokens_data'])) ? $data['data']['tokens_data'] : "";
        $variant_id = (isset($data['data']['variant_id'])) ? $data['data']['variant_id'] : 1;
        $variant_step_id = (isset($data['data']['variant_step_id'])) ? $data['data']['variant_step_id'] : 1;

        $response = array();
        try {

            // apply campaign validation checks
            $validation = BoardValidation::validation($board_id);

            $_tracking_keys = [];
            $device_tokens = [];
            $server_key = '';
            $device_type = strtolower(AppPlatforms::PLATFORM_ANDROID);
            foreach ($tokens_data as $key => $value) {

                $device_type = strtolower($value['device_type']);
                if ($device_type == strtolower(AppPlatforms::PLATFORM_ANDROID)) {
                    $server_key = $value['server_key'];
                } elseif ($device_type == strtolower(AppPlatforms::PLATFORM_IOS)) {
                    $server_key = $value['server_key'];
                } elseif ($device_type == strtolower(AppPlatforms::PLATFORM_WEB)) {
                    $server_key = $value['server_key'];
                } else {
                    $server_key = $value['server_key'];
                }

                foreach ($value['tracking_key'] as $key => $tracking) {
                    $trackingKey = $tracking;
                    $_tracking_keys[] = $tracking;
                }

                foreach ($value['device_token'] as $key => $_tokens) {
                    $device_tokens[] = $_tokens;
                }
            }
            $data['data']['track_keys'] = $_tracking_keys;

            $_params = [
                "title" => (isset($data['notification']['title'])) ? $data['notification']['title'] : "",
                "body" => (isset($data['notification']['body'])) ? $data['notification']['body'] : "",
                "link" => (isset($data['data']['action_value'])) ? $data['data']['action_value'] : "",
                "backgroundColor" => (isset($data['data']['backgroundColor'])) ? $data['data']['backgroundColor'] : "#FFFFFF",
                "device_type" => (isset($tokens_data['device_type'])) ? $tokens_data['device_type'] : $device_type, // remove later on
                "device_token" => '', // remove later on
                "app_group_id" => $app_group_id,
                "code" => (isset($data['data']['code'])) ? $data['data']['code'] : "",
                "user_id" => (isset($data['data']['user_id'])) ? $data['data']['user_id'] : "",
                "track_keys" => (isset($data['data']['track_keys'])) ? $data['data']['track_keys'] : [],
                "action_url" => (isset($data['data']['action_url'])) ? $data['data']['action_url'] : "",
                "is_hermis_platform" => $data['data']['is_hermis_platform'],
                "is_silent" => $data['data']['is_silent'],
                "type" => $data['data']['type'],
                "message_position" => $data['data']['message_position'],
                "message_type" => (isset($data['data']['message_type'])) ? $data['data']['message_type'] : "",
                "priority" => $data['data']['priority'],
                "icon" => ($data['data']['icon'] != "") ? $data['data']['icon'] : "",
                "auto_close" => $data['data']['auto_close'],
                "action_type" => (isset($data['data']['action_type'])) ? $data['data']['action_type'] : "deep link",
                "action_value" => (isset($data['data']['action_value'])) ? $data['data']['action_value'] : "",
                "dispatch_date" => (isset($data['data']['dispatch_date'])) ? $data['data']['dispatch_date'] : "",
                "view_link" => $data['data']['view_link'],
                "is_board" => true
            ];

            $content_available = 0;
            if ((bool)$_params['is_silent'] == true) {
                $content_available = 1;
            }

            if ( strtolower($_params['type']) == strtolower(BoardVariant::VARIANT_PUSH_CODE) ) { // && strtolower($_params['device_type']) == AppPlatforms::PLATFORM_IOS

                // generating notification payload for push campaigns
                $notification = CampaignWorkerPayload::generatePayloadNotification($_params);
            } else {
                $notification = null;
            }
            //Log::info('Worker payload notify: ' . \GuzzleHttp\json_encode($notification));

            $payload_data = CampaignWorkerPayload::generateBoardPayloadData($_params);
            //Log::info('Worker payload data: ' . \GuzzleHttp\json_encode($payload_data));

            /*\Artisan::call('config:cache');
            \Config::set('fcm.http.server_key', $server_key);*/

            if (isset($device_tokens) && count($device_tokens) > 0) {

                $notifications = new NotificationController($device_tokens, $notification, $payload_data, $server_key, $content_available);
                $response = $notifications->sendNotification();
                Log::info('board-response: ' . \GuzzleHttp\json_encode($response, true));
                if (isset($response) && count($response) > 0) {

                    // prepare and parse response params
                    $number_success = (isset($response['numberSuccess'])) ? $response['numberSuccess'] : 0;
                    $numberFailure = (isset($response['numberFailure'])) ? $response['numberFailure'] : 0;
                    $tokensToDelete = (isset($response['tokensToDelete'])) ? $response['tokensToDelete'] : [];
                    $tokensWithError = (isset($response['tokensWithError'])) ? $response['tokensWithError'] : [];

                    $is_sent = true;
                    if (!empty($data['data']['track_keys'])) {
                        foreach ($data['data']['track_keys'] as $_track_key) {

                            // preparing device key
                            $_device_key = (isset($data['data']['tokens_data'][0]['device_token'][0])) ? $data['data']['tokens_data'][0]['device_token'][0] : "";

                            if ((int)$number_success > 0) {

                                //Log::info('Notification send with status: ' . AppStatusCodes::HTTP_OK);
                                $success_tokens = [];
                                $success_tokens = array_diff($device_tokens, $tokensToDelete);

                                if (in_array($_device_key, $success_tokens)) {

                                    // updating campaign tracking with status
                                    DB::update(" Update board_tracking SET status='".Board::BOARD_TRACKING_COMPLETED_STATUS."',
                                        message='Notification sent successfully.', sent=1, sent_at='".Carbon::now()->format('Y-m-d h:i:s')."',
                                        ended_at='".Carbon::now()->format('Y-m-d h:i:s')."'
                                        where track_key = '".$_track_key."' ");
                                }
                            }
                            if (isset($tokensToDelete)) {

                                // updating device token revoked status
                                $result = CommonHelper::updateDeviceToken($tokensToDelete, [
                                    "app_group_id" => $app_group_id,
                                    'is_revoked' => '1',
                                    'status' => '0',
                                    'deleted_at' => Carbon::now()->format('Y-m-d h:i:s')
                                ]);

                                if (in_array($_device_key, $tokensToDelete)) {

                                    // updating campaign tracking with status
                                    DB::update("Update board_tracking SET status='" . Board::BOARD_TRACKING_FAILED_STATUS . "',
                                                        message='Notification sending failed due to expire token(s).',                                        
                                                        sent_at='" . Carbon::now()->format('Y-m-d h:i:s') . "',
                                                        ended_at='" . Carbon::now()->format('Y-m-d h:i:s') . "',
                                                        payload='". \GuzzleHttp\json_encode($this->payload, true) ."'
                                                        where track_key = '" . $_track_key . "'");
                                }
                            }
                            if ((int)$numberFailure == count($device_tokens) && !empty($tokensWithError)) {

                                $notification_error = "";
                                if (in_array($_device_key, $device_tokens)) {

                                    $error_token = (isset($tokensWithError[$_device_key])) ? $tokensWithError[$_device_key] : "";
                                    if ($error_token == "MessageTooBig") {
                                        $notification_error = "`MessageTooBig`, Payload size is too big.";
                                    } elseif ($error_token == "InvalidApnsCredential") {
                                        $notification_error = "`InvalidApnsCredential`, expire token(s).";
                                    } else {
                                        $notification_error = "expire token(s).";
                                    }

                                    // updating campaign tracking with status
                                    $message = 'Notification sending failed due to '.$notification_error;
                                    DB::update("Update board_tracking SET status='" . Board::BOARD_TRACKING_FAILED_STATUS . "',
                                                        message='". $message."', sent_at='" . Carbon::now()->format('Y-m-d h:i:s') . "',
                                                        ended_at='" . Carbon::now()->format('Y-m-d h:i:s') . "',
                                                        payload='". \GuzzleHttp\json_encode($this->payload, true) ."'
                                                        where track_key = '" . $_track_key . "'");
                                }
                                $is_sent = false;
                            }
                            if ((int)$numberFailure == count($device_tokens) && empty($tokensWithError)) {

                                $notification_error = "expired token(s).";
                                if (in_array($_device_key, $device_tokens)) {

                                    // updating campaign tracking with status
                                    DB::update("Update board_tracking SET  status='" . Board::BOARD_TRACKING_FAILED_STATUS . "',
                                        message='Notification sending failed due to expired token(s).',
                                        sent_at='" . Carbon::now()->format('Y-m-d h:i:s') . "',
                                        ended_at='" . Carbon::now()->format('Y-m-d h:i:s') . "',
                                        payload='". \GuzzleHttp\json_encode($this->payload, true) ."'
                                        where track_key = '" . $_track_key . "'");
                                }
                                $is_sent = false;
                            }
                            else{
                                $is_sent = false;
                            }

                            // update tracking cache
                            $last_sent_date = Carbon::now()->toDateTimeString();
                            $_tracking = new BoardUserTrackingCache();
                            $_tracking->updateBoardTrackingCache($board_id, $row_id, $variant_id, $variant_step_id, $last_sent_date);

                            //$campaign = Campaign::find($campaign_id);
                            //CampaignCappingControl::setCappingInfo($campaign, $row_id, $language, $variant_id, $_tracking_sent_at);
                        }
                    }
                    else{
                        $is_sent = false;
                    }
                    if (!$is_sent) {
                        $response = [
                            'status' => 'failed',
                            'message' => 'Notification sending failed.'
                        ];
                    } else {
                        $response = [
                            'status' => 'success',
                            'message' => 'Notification sent successfully.'
                        ];
                    }
                }
            }
            return $response;
        } catch (\Exception $exception) {

            $track_keys = (isset($data['tokens_data']['tracking_key'])) ? $data['tokens_data']['tracking_key'] : [];
            if (!empty($track_keys)) {
                foreach ($track_keys as $_track_key) {

                    // updating campaign tracking with status
                    DB::update("Update board_tracking SET status='" . Board::BOARD_TRACKING_FAILED_STATUS . "',
                                        message= '".$exception->getMessage()."', sent_at='" . Carbon::now()->format('Y-m-d h:i:s') . "',
                                        ended_at='" . Carbon::now()->format('Y-m-d h:i:s') . "',
                                        payload='". \GuzzleHttp\json_encode($this->payload, true) ."'
                                        where track_key = '" . $_track_key . "'");

                    // update tracking cache
                    $last_sent_date = Carbon::now()->toDateTimeString();
                    $_tracking = new BoardUserTrackingCache();
                    $_tracking->updateBoardTrackingCache($board_id, $row_id, $variant_id, $variant_step_id, $last_sent_date);
                }
            }
            $response = [
                'status' => 'error',
                'message' => $exception->getMessage()
            ];
            //\Artisan::call('config:clear');
            Log::error('Board Failed Exception: ' . $exception->getMessage());

            return $response;
        }
    }
}
