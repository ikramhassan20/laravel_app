<?php

namespace App\Components;

use App\Campaign;
use App\CampaignTracking;
use App\Cache\CampaignTrackingCache;
use App\CampaignTrackingLog;
use App\Components\CampaignWorkerPayload;
use App\Helpers\CampaignValidation;
use App\Http\Resources\V1\Notifications\SendAndroidNotifications;
use App\Http\Resources\V1\Notifications\SendNotifications;
use App\Http\Controllers\NotificationController;
use App\Helpers\CommonHelper;
use Carbon\Carbon;
use Composer\Util\Platform;
use Illuminate\Support\Facades\Log;

class CampaignQueueComponent
{
    use InteractsWithMessages;

    /**
     * @var string $type
     */
    protected $type;

    /**
     * @var array $payload
     */
    protected $payload;

    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $campaign;

    /**
     * TargetedUsers constructor.
     *
     * @param mixed $payload
     */
    public function __construct($payload = null)
    {
        $this->payload = $payload;
    }

    public function process()
    {
        $response = array();
        try {

            Log::emergency('Worker job processing...');
            $data = $this->payload;
            $campaign_id = (isset($data['data']['campaign_id'])) ? $data['data']['campaign_id'] : "";
            $user_id = (isset($data['data']['user_id'])) ? $data['data']['user_id'] : "";
            $row_id = (isset($data['data']['row_id'])) ? $data['data']['row_id'] : "";
            $tokens_data = (isset($data['data']['tokens_data'])) ? $data['data']['tokens_data'] : "";
            $language =  (isset($data['data']['language'])) ? $data['data']['language'] : "";

            // apply campaign validation checks
            $validation = CampaignValidation::validation($campaign_id);

            $_tracking_keys=[];$device_tokens = [];$server_key='';
            $campaign_tracking_id = [];
            foreach($tokens_data as $key=>$value){

                // device_type
                $device_type = strtolower($value['device_type']);
                if( $device_type == 'android') {
                    $server_key = $value['server_key'];
                    //$server_key = "AAAAGOFAepI:APA91bHCNaJ6KAOFvivnQcCcbLfouFud56KSoLvuuGjWSFlvHu6-3tFSqd5F8ZMKlfj6UXpi6yDLGXo3QdKLdnk56Z3yY2lFn2uzIkk5bITzhy51hOKVXHSJ3VCd2oAj-T6bxVJxfP3e";
                }
                elseif( $device_type == 'ios') {
                    $server_key = $value['server_key'];
                    //$server_key = "AAAAHtc5HBo:APA91bH3hvkYhGYPzJ9vdFETqXKwBFJShdMExDbMbp4BYpNGAhZEq-r7H0QjYkTVGMPCqA0qkpJVxpkBiCOpFiGNVgRiHdmvTEIqbO-qWx7d36kfvPDzvHs0CDnv-1suENbVVSz7dl1oYwavUtude4g-8A-hyWPtHQ";
                }
                else{
                    $server_key = $value['server_key'];
                }
                //dump($device_type,$server_key);

                foreach($value['tracking_key'] as $key=>$tracking){
                    $trackingKey = $tracking;
                    $_tracking_keys[] = $tracking;

                    // Checking Campaign Tracking id is valid
                    $campaignTracking = CampaignTracking::where('track_key', '=', $trackingKey)
                        ->where('campaign_id', '=', $campaign_id)
                        ->first();
                    if (!$campaignTracking) {
                        $tracking_error = "Tracking key is in-valid.";
                        Log::error($tracking_error);
                        //throw new \Exception($tracking_error);
                    }

                    $campaign_tracking_id[] = (isset($campaignTracking->id)) ? $campaignTracking->id : "";
                    Log::info('Campaign tracking found.');

                    // update the Campaign Tracking status from added to executing
                    $this->updateTrackingStatus($trackingKey, Campaign::CAMPAIGN_TRACKING_EXECUTING_STATUS);
                }

                foreach($value['device_token'] as $key=>$_tokens){
                    $device_tokens[] = $_tokens;
                }
                //dump($device_tokens);

            }
            //dd($android_server_key ." : ". $ios_server_key);

            $data['data']['track_keys'] = $_tracking_keys;

            $_params = [
                "title" => (isset($data['notification']['title'])) ? $data['notification']['title'] : "",
                "body" => (isset($data['notification']['body'])) ? $data['notification']['body'] : "",
                "backgroundColor" => (isset($data['data']['backgroundColor'])) ? $data['data']['backgroundColor'] : "#FFFFFF",
                "message_position" => $data['data']['message_position'],
                "device_type" => AppPlatforms::PLATFORM_ANDROID, // remove later on
                "device_token" => '', // remove later on
                "campaign_code" => (isset($data['data']['campaign_code'])) ? $data['data']['campaign_code'] : "",
                "user_id" => (isset($data['data']['user_id'])) ? $data['data']['user_id'] : "",
                "track_keys" => (isset($data['data']['track_keys'])) ? $data['data']['track_keys'] : [],
                "action_url" => (isset($data['data']['action_url'])) ? $data['data']['action_url'] : "",
                "is_hermis_platform" => $data['data']['is_hermis_platform'],
                "is_silent" => $data['data']['is_silent'],
                "campaign_type" => $data['data']['campaign_type'],
                'message_position' => $data['data']['message_position'],
                "message_type" => (isset($data['data']['message_type'])) ? $data['data']['message_type'] : "",
                "priority" => $data['data']['priority'],
                "icon" => ($data['data']['icon'] != "") ? $data['data']['icon'] : "",
                "action_type" => (isset($data['data']['action_type'])) ? $data['data']['action_type'] : "deep link",
                "action_value" => (isset($data['data']['action_value'])) ? $data['data']['action_value'] : "",
                "message" => $data['data']['message'],
                "view_link" => $data['data']['view_link']
            ];
            //dump($_params);

            $content_available = 0;
            if((bool)$_params['is_silent'] == true){
                $content_available = 1;
            }

            if($_params['campaign_type'] == Campaign::CAMPAIGN_INAPP_CODE){
                $notification = null;
            }
            else{
                $_params['link'] = "";
                if(strtolower($_params['device_type']) == AppPlatforms::PLATFORM_ANDROID){
                    if($_params['action_type'] == 'deep link'){
                        $_params['link'] = $_params['action_value'];
                    }
                }
                $notification = CampaignWorkerPayload::generatePayloadNotification($_params);
                Log::info('Worker payload notify: ' . \GuzzleHttp\json_encode($notification));
            }
            //dump($notification);
            //dump($content_available);

            $payload_data = CampaignWorkerPayload::generatePayloadData($_params);
            Log::info('Worker payload data: ' . \GuzzleHttp\json_encode($payload_data));
            //dump($payload_data);
            //dump($payload_data['data']['track_key']);

            \Artisan::call('config:cache');
            \Config::set('fcm.http.server_key', $server_key);

            if(isset($device_tokens) && count($device_tokens) > 0 ) {

                $notifications = new NotificationController($device_tokens, $notification, $payload_data, $server_key, $content_available);
                $response = $notifications->sendNotification();
                //dump($response);
                if(isset($response) && count($response) > 0){

                    // prepare and parse response params
                    $number_success = (isset($response['numberSuccess'])) ? $response['numberSuccess'] : 0;
                    $numberFailure = (isset($response['numberFailure'])) ? $response['numberFailure'] : 0;
                    $tokensToDelete = (isset($response['tokensToDelete'])) ? $response['tokensToDelete'] : [];

                    $is_sent=true;
                    if(!empty($data['data']['track_keys'])){
                        foreach($data['data']['track_keys'] as $_track_key){
                            $_tracking = CampaignTracking::where('track_key', $_track_key)->first();
                            $_device_key = $_tracking->device_key;
                            $_tracking_id = $_tracking->id;
                            $variant_id = (isset($_tracking->variant_id)) ? $_tracking->variant_id : 1;

                            if((int)$number_success > 0 ) {

                                Log::info('Notification send with status: ' . AppStatusCodes::HTTP_OK);
                                $success_tokens = [];
                                $success_tokens = array_diff($device_tokens, $tokensToDelete);

                                if(in_array($_device_key, $success_tokens)){
                                    $_tracking->status=Campaign::CAMPAIGN_TRACKING_COMPLETED_STATUS;
                                    $_tracking->sent='1';
                                    $_tracking->sent_at=Carbon::now()->format('Y-m-d h:i:s');
                                    $_tracking->ended_at=Carbon::now()->format('Y-m-d h:i:s');
                                    $_tracking->save();
                                    Log::info('Campaign tracking status updated.');

                                    $tracking_log=[];
                                    $tracking_log['campaign_tracking_id'] = $_tracking_id;
                                    $tracking_log['status'] = Campaign::CAMPAIGN_TRACKING_COMPLETED_STATUS;
                                    $tracking_log['message'] = 'Notification sent successfully.';
                                    CampaignTrackingLog::create($tracking_log);
                                    Log::info('Campaign tracking logs updated.');

                                    // update tracking cache
                                    //$language = $payload_data['data']['language'];
                                    $last_sent_date = Carbon::now()->toDateTimeString();
                                    $sent_count = 1;
                                    $_tracking = new CampaignTrackingCache();
                                    $content = $data['data']['message'];
                                    Log::info("body: " . $content);
                                    $_tracking->updateCampaignTrackingCache($campaign_id, $row_id, $language, $variant_id, $content, $last_sent_date, $sent_count);
                                    Log::info('Campaign tracking cache updated.');
                                }
                            }
                            if( isset($tokensToDelete) ) {
                                Log::info('Notification: tokens to delete.');
                                $result = CommonHelper::updateDeviceToken($tokensToDelete, [
                                    'is_revoked' => '1',
                                    'status' => '0',
                                    'deleted_at' => Carbon::now()->format('Y-m-d h:i:s')
                                ]);

                                if(in_array($_device_key, $tokensToDelete)){
                                    $_tracking->status=Campaign::CAMPAIGN_TRACKING_FAILED_STATUS;
                                    $_tracking->sent_at=Carbon::now()->format('Y-m-d h:i:s');
                                    $_tracking->ended_at=Carbon::now()->format('Y-m-d h:i:s');
                                    $_tracking->save();
                                    Log::info('Campaign tracking failed status updated.');

                                    $tracking_log=[];
                                    $tracking_log['campaign_tracking_id'] = $_tracking_id;
                                    $tracking_log['status'] = Campaign::CAMPAIGN_TRACKING_FAILED_STATUS;
                                    $tracking_log['message'] = 'Notification sending failed.';
                                    CampaignTrackingLog::create($tracking_log);
                                    Log::info('Campaign tracking logs updated.');
                                }
                            }
                            if( (int)$numberFailure == count($device_tokens) ){
                                Log::info('Notification: number failure equal to device tokens.');
                                if(in_array($_device_key, $device_tokens)){
                                    $_tracking->status=Campaign::CAMPAIGN_TRACKING_FAILED_STATUS;
                                    $_tracking->sent_at=Carbon::now()->format('Y-m-d h:i:s');
                                    $_tracking->ended_at=Carbon::now()->format('Y-m-d h:i:s');
                                    $_tracking->save();
                                    Log::info('Campaign tracking failed status updated.');

                                    $tracking_log=[];
                                    $tracking_log['campaign_tracking_id'] = $_tracking_id;
                                    $tracking_log['status'] = Campaign::CAMPAIGN_TRACKING_FAILED_STATUS;
                                    $tracking_log['message'] = 'Notification sending failed.';
                                    CampaignTrackingLog::create($tracking_log);
                                    Log::info('Campaign tracking logs updated.');
                                }
                                $is_sent=false;
                            }
                        }
                    }
                    if(!$is_sent){
                        $response = [
                            'status' => 'failed',
                            'message' => 'Notification sending failed.'
                        ];
                    }
                    else{
                        $response = [
                            'status' => 'success',
                            'message' => 'Notification sent successfully.'
                        ];
                    }
                    /*if((int)$number_success > 0 ){

                        Log::info('Notification send with status: ' . AppStatusCodes::HTTP_OK);

                        $success_tokens = [];
                        $success_tokens = array_diff($device_tokens, $tokensToDelete);
                        //dump($success_tokens);
                        if(!empty($data['data']['track_keys'])){
                            foreach($data['data']['track_keys'] as $_track_key){
                                $_tracking = CampaignTracking::where('track_key', $_track_key)->first();
                                if($_tracking){
                                    $track_key = $_tracking->device_key;
                                    $_tracking_id = $_tracking->id;
                                    if(in_array($track_key, $success_tokens)){
                                        $_tracking->status=Campaign::CAMPAIGN_TRACKING_COMPLETED_STATUS;
                                        $_tracking->sent='1';
                                        $_tracking->sent_at=Carbon::now()->format('Y-m-d h:i:s');
                                        $_tracking->ended_at=Carbon::now()->format('Y-m-d h:i:s');
                                        $_tracking->save();
                                        Log::info('Campaign tracking status updated.');
                                    }

                                    $tracking_log=[];
                                    $tracking_log['campaign_tracking_id'] = $_tracking_id;
                                    $tracking_log['status'] = Campaign::CAMPAIGN_TRACKING_COMPLETED_STATUS;
                                    $tracking_log['message'] = 'Notification sent successfully.';
                                    CampaignTrackingLog::create($tracking_log);
                                    Log::info('Campaign tracking logs updated.');

                                    // update tracking cache
                                    //$language = $payload_data['data']['language'];
                                    $last_sent_date = Carbon::now()->toDateTimeString();
                                    $sent_count = 1;
                                    $_tracking = new CampaignTrackingCache();
                                    $content = $data['data']['message'];
                                    Log::info("body: " . $content);
                                    $_tracking->updateCampaignTrackingCache($campaign_id, $row_id, $language, $content, $last_sent_date, $sent_count);
                                    Log::info('Campaign tracking cache updated.');
                                }
                            }
                        }
                    }
                    if( isset($tokensToDelete) ){
                        $result = CommonHelper::updateDeviceToken($tokensToDelete, [
                            'is_revoked' => '1',
                            'status' => '0',
                            'deleted_at' => Carbon::now()->format('Y-m-d h:i:s')
                        ]);
                        foreach($tokensToDelete as $_token){
                            $_tracking = CampaignTracking::where('device_key', $_token)->where('campaign_id', '=', $campaign_id)->first();
                            if($_tracking){
                                $_tracking_id = $_tracking->id;
                                $_tracking->status=Campaign::CAMPAIGN_TRACKING_FAILED_STATUS;
                                $_tracking->sent_at=Carbon::now()->format('Y-m-d h:i:s');
                                $_tracking->ended_at=Carbon::now()->format('Y-m-d h:i:s');
                                $_tracking->save();
                                Log::info('Campaign tracking failed status updated.');

                                $tracking_log=[];
                                $tracking_log['campaign_tracking_id'] = $_tracking_id;
                                $tracking_log['status'] = Campaign::CAMPAIGN_TRACKING_FAILED_STATUS;
                                $tracking_log['message'] = 'Notification sending failed.';
                                CampaignTrackingLog::create($tracking_log);
                                Log::info('Campaign tracking logs updated.');
                            }
                        }
                    }
                    if( (int)$numberFailure == count($device_tokens) ){
                        //dump($device_tokens);
                        foreach($payload_data['data']['track_key'] as $track_key){
                            $_tracking = CampaignTracking::where('track_key', $track_key)->first();
                            //dump($_tracking);
                            if($_tracking){
                                $_tracking_id = $_tracking->id;
                                $_tracking->status=Campaign::CAMPAIGN_TRACKING_FAILED_STATUS;
                                $_tracking->sent_at=Carbon::now()->format('Y-m-d h:i:s');
                                $_tracking->ended_at=Carbon::now()->format('Y-m-d h:i:s');
                                $_tracking->save();
                                Log::info('Campaign tracking failed status updated.');

                                $tracking_log=[];
                                $tracking_log['campaign_tracking_id'] = $_tracking_id;
                                $tracking_log['status'] = Campaign::CAMPAIGN_TRACKING_FAILED_STATUS;
                                $tracking_log['message'] = 'Notification sending failed.';
                                CampaignTrackingLog::create($tracking_log);
                                Log::info('Campaign tracking logs updated.');
                            }
                        }
                        $response = [
                            'status' => 'failed',
                            'message' => 'Notification sending failed.'
                        ];
                        return $response;
                    }*/
                }
            }
            return $response;
        } catch (\Exception $exception) {

            $track_keys = (isset($data['tokens_data']['tracking_key'])) ? $data['tokens_data']['tracking_key'] : [];
            if(!empty($track_keys)){
                foreach($track_keys as $_track_key){
                    $_tracking = CampaignTracking::where('track_key', $_track_key)->first();
                    if($_tracking){
                        $_tracking_id = $_tracking->id;
                        $_tracking->status=Campaign::CAMPAIGN_TRACKING_FAILED_STATUS;
                        $_tracking->sent_at=Carbon::now()->format('Y-m-d h:i:s');
                        $_tracking->ended_at=Carbon::now()->format('Y-m-d h:i:s');
                        $_tracking->save();
                        Log::info('Campaign tracking failed status updated.');

                        $tracking_log=[];
                        $tracking_log['campaign_tracking_id'] = $_tracking_id;
                        $tracking_log['status'] = Campaign::CAMPAIGN_TRACKING_FAILED_STATUS;
                        $tracking_log['message'] = $exception->getMessage();
                        CampaignTrackingLog::create($tracking_log);
                        Log::info('Campaign tracking failed logs updated.');
                    }
                }
            }
            $response = [
                'status' => 'error',
                'message' => $exception->getMessage()
            ];
            Log::info('Worker Failed Exception: ' . $exception);
            return $response;
        }
    }
}
