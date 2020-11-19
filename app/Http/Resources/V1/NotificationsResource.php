<?php

namespace App\Http\Resources\V1;

use App\Components\AppStatusCodes;
use App\Components\MessageFormatter;
use App\Components\ParseResponse;
use App\Components\CampaignWorkerPayload;
use App\Helpers\CampaignValidation;
use App\Helpers\CommonHelper;
use App\Http\Controllers\NotificationController;
use App\Components\AppPlatforms;
use App\Campaign;
use App\Apps;
use App\Http\Resources\Contracts\ProcessResourceDataContract;
use App\Language;
use App\Lookup;
use App\Notification;
use App\NotificationsLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class NotificationsResource implements ProcessResourceDataContract
{
    use ParseResponse;

    /**
     * Process resource data.
     *
     * @param array|\Illuminate\Http\Request $request
     * @param \Illuminate\Database\Eloquent\Model $model
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function process($request, \Illuminate\Database\Eloquent\Model $model)
    {
        $response = array();$_response = array();
        try {
            $user = $request->user();
            $data = $this->parseResponse($request);
            $appId = $request->header('app-id');
            $appName = $request->header('app-name');
            $devicetype = $request->header('device-type');
            $data['appId'] = $appId;
            $data['appName'] = $appName;
            $data['deviceType'] = $devicetype;
            $validator = new Notifications\ValidateRequest($data);
            if (!empty($validator->errors)) {
                return $this->addResponse(
                    AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                    'error',
                    $validator->errors,
                    'error'
                );
            }

            $data['company_id'] = $user->id;
            $data['app_group_id'] = $user->currentAppGroup()->id;

            $compileRecipients = new Notifications\CompileRecipients($user, $data);
            $receivers = $compileRecipients->compile();
            if (count($receivers) > 0) {
                $finalResponse = array();
                for ($val = 0; $val < count($receivers); $val++) {
                    foreach($receivers[$val] as $row_id=>$app_users){

                        if ($row_id == "") {
                            Log::error("App user row id not found.");
                            continue;
                        }

                        if (isset($app_users) && count($app_users) > 0) {
                            // getting user email
                            $to_email = (isset($app_users[0]['email'])) ? $app_users[0]['email'] : "";
                            $user_id = (isset($app_users[0]['user_id'])) ? $app_users[0]['user_id'] : "";
                            $app_user_lang = (isset($app_users[0]['lang'])) ? $app_users[0]['lang'] : "en";

                            // set campaign dispatch date time in UTC
                            //$timestamp = date("Y-m-d h:i:s");
                            //$campaign_dispatch_date = Carbon::createFromFormat('Y-m-d H:i:s', $timestamp, 'Asia/Dubai');
                            //$campaign_dispatch_date->setTimezone('UTC');
                            $campaign_dispatch_date = Carbon::parse(Carbon::now())->setTimezone("UTC")->format('Y-m-d');

                            $_app_users=[];
                            foreach ($app_users as $key => $_user_tokens) {
                                $data['type'] = strtolower($data['type']);
                                if ($data['type'] == 'email') {
                                    $_user_tokens['from_email'] = $data['email']['from_email'];
                                    $_user_tokens['subject'] = $data['email']['subject'];
                                    $_user_tokens['message'] = $data['message'];
                                    $_user_tokens['html_content'] = '';
                                    $data['device_type'] = strtolower($data['type']);

                                    $_user_tokens['subject'] = (new MessageFormatter)->apply_attribute($row_id, $data['company_id'], $_user_tokens['subject']);
                                } else {
                                    $_user_tokens['title'] = $data['title'];
                                    $_user_tokens['message'] = $data['message'];
                                    $_user_tokens['html_content'] = (isset($data['html_content'])) ? $data['html_content'] : "";
                                    $data['device_type'] = strtolower($data['platform']);

                                    $_user_tokens['title'] = (new MessageFormatter)->apply_attribute($row_id, $data['company_id'], $_user_tokens['title']);
                                }
                                $msgFormatResponse = (new MessageFormatter)->apply_attribute($row_id, $data['company_id'], $_user_tokens['message']);
                                $_user_tokens['message'] = $msgFormatResponse;
                                if ($_user_tokens['html_content'] != "") {
                                    $msgFormatResponse = (new MessageFormatter)->apply_attribute($row_id, $data['company_id'], $_user_tokens['html_content']);
                                    $_user_tokens['html_content'] = $msgFormatResponse;
                                }
                                $_app_users[] = $_user_tokens;
                            }
                            if ($data['type'] == Campaign::CAMPAIGN_INAPP_CODE || $data['type'] == Campaign::CAMPAIGN_PUSH_CODE) {

                                /*******************************************************************/
                                // building app user tokens
                                /*******************************************************************/
                                $device_users = [
                                    'android' => [],
                                    'ios'   => [],
                                    'web'   => []
                                ];
                                foreach ($_app_users as $key => $_user_tokens) {

                                    // getting app user tokens info
                                    $data['platform'] = strtolower($data['platform']);

                                    if($data['platform'] == $_user_tokens['device_type'] || $data['platform'] == 'universal'){
                                        //dump($_user_tokens);
                                        $device_users[$_user_tokens['device_type']][] = $_user_tokens;
                                    }
                                }

                                // loop through each device types
                                $tokens_data = [];
                                foreach ($device_users as $key => $app_users) {

                                    // device type
                                    $device_type = $key;
                                    //print_r($app_users);

                                    $android_server_key = "";$ios_server_key="";$web_server_key="";
                                    $device_tokens = [];
                                    //dump($app_users);
                                    if (isset($app_users) && count($app_users) > 0) {
                                        foreach ($app_users as $key => $app_user_token_object) {

                                            $android_server_key = (isset($app_user_token_object['android_server_key'])) ? $app_user_token_object['android_server_key'] : "";
                                            $ios_server_key = (isset($app_user_token_object['ios_server_key'])) ? $app_user_token_object['ios_server_key'] : "";
                                            $web_server_key = (isset($app_user_token_object['web_server_key'])) ? $app_user_token_object['web_server_key'] : "";
                                            if ($android_server_key == "" && $ios_server_key == "" && $web_server_key == "") {
                                                $error = 'Server Key not found. ';
                                                Log::info($error);
                                                continue;
                                            }
                                            $device_token = (isset($app_user_token_object['device_token'])) ? $app_user_token_object['device_token'] : "";
                                            if ($device_token == "") {
                                                Log::info("Device token not found.");
                                                continue;
                                            }
                                            $device_tokens[] = $device_token;
                                        }

                                        $tokens_data[] = array(
                                            'android_server_key' => $android_server_key,
                                            'ios_server_key' => $ios_server_key,
                                            'web_server_key' => $web_server_key,
                                            'device_type' => $device_type,
                                            'device_token' => $device_tokens,
                                            'tracking_key' => [],
                                        );
                                    }
                                    //dump($tokens_data);
                                }
                                //dump($tokens_data);
                                $action_type = (isset($data['action']['type'])) ? $data['action']['type'] : "";
                                $action_value = $data['action']['value'];

                                $image_url = "";
                                if ($data['type'] == Campaign::CAMPAIGN_PUSH_CODE){
                                    $_app_users[0]['html_content'] = $_app_users[0]['message'];
                                    $image_url = (isset($data['image_url'])) ? $data['image_url'] : "";

                                    if($image_url == ""){
                                        $app_user_app_id = (isset($_app_users[0]['app_id'])) ? $_app_users[0]['app_id'] : "";
                                        $app_group_id = (isset($_app_users[0]['app_group_id'])) ? $_app_users[0]['app_group_id'] : "";
                                        $image_url = CommonHelper::getAppIcon($app_user_app_id, $app_group_id, $data['device_type']);
                                    }
                                }

                                if(isset($data['auto_close']) && $data['auto_close'] != ""){
                                    $auto_close = $data['auto_close'];
                                }
                                else{
                                    $auto_close = true;
                                }

                                $title = (isset($_app_users[0]['title'])) ? $_app_users[0]['title'] : "";
                                $message = (isset($_app_users[0]['message'])) ? $_app_users[0]['message'] : "";
                                $html_content = (isset($_app_users[0]['html_content'])) ? $_app_users[0]['html_content'] : "";

                                if ($data['type'] == Campaign::CAMPAIGN_PUSH_CODE){
                                    $message = $html_content;
                                }

                                if($html_content == "") $html_content = $message;

                                /*if($data['type'] == Campaign::CAMPAIGN_INAPP_CODE){
                                    if(strtolower($data['message_type']) == Lookup::LOOKUP_CODE_BANNER && $message == ""){
                                        $title = Campaign::CAMPAIGN_INAPP_BANNER_NOTIFICATION_TITLE;
                                        $message = Campaign::CAMPAIGN_INAPP_BANNER_NOTIFICATION_BODY;
                                    }
                                    if(strtolower($data['message_type']) == Lookup::LOOKUP_CODE_BANNER && $html_content == ""){
                                        $html_content = Campaign::CAMPAIGN_INAPP_BANNER_NOTIFICATION_BODY;
                                    }
                                } */

                                if(isset($tokens_data) && count($tokens_data) > 0){
                                    $_params = [
                                        'title' => $title,
                                        'body' => $message,
                                        'icon' => $image_url,
                                        'device_type' => $data['device_type'],
                                        'device_token' => "",
                                        "app_group_id" => $data['app_group_id'],
                                        'code' => "",
                                        'message_type' => (isset($data['message_type'])) ? str_replace("dialog", "dialogue", strtolower($data['message_type'])) : "dialogue",
                                        'track_key' => "",
                                        'user_id' => $_app_users[0]['user_id'],
                                        'link' => $action_value,
                                        'type' => $data['type'], // inapp / push
                                        'message_position' => (isset($data['message_position'])) ? strtolower($data['message_position']) : "top",
                                        "view_link" => "",
                                        'priority' => "normal",
                                        "is_hermis_platform" => true,
                                        "is_silent" => false,
                                        "backgroundColor" => "#FFFFFF",
                                        "auto_close" => $data['auto_close'],
                                        "action_type" => $action_type,
                                        "action_value" => $action_value,
                                        "language" => $app_user_lang,
                                        "tokens_data" => $tokens_data,
                                        "dispatch_date" => $campaign_dispatch_date,
                                        "is_board" => (isset($data['is_board'])) ? (bool)$data['is_board'] : false,
                                        "sound" => "DEFAULT"
                                    ];
                                    //dump($_params);

                                    $content_available = 0;
                                    if((bool)$_params['is_silent'] == true){
                                        $content_available = 1;
                                    }

                                    if( $data['type'] == Campaign::CAMPAIGN_PUSH_CODE ){ // && strtolower($_params['device_type']) == AppPlatforms::PLATFORM_IOS

                                        // generating notification payload for push campaigns
                                        $notification = CampaignWorkerPayload::generatePayloadNotification($_params);
                                    }
                                    else{
                                        $notification = null;
                                    }
                                    Log::info('Worker payload notify: ' . \GuzzleHttp\json_encode($notification));

                                    $payload_data = CampaignWorkerPayload::generatePayloadData($_params);
                                    Log::info('Worker payload data: ' . \GuzzleHttp\json_encode($payload_data));
                                    //dump($payload_data);

                                    $payload_sandbox = CampaignWorkerPayload::generateWorkerPayloadSandbox($notification, $payload_data);

                                    $server_key = "";$device_tokens=[];
                                    foreach ($tokens_data as $key => $value) {
                                        // device_type
                                        $device_type = strtolower($value['device_type']);
                                        if ($device_type == strtolower(AppPlatforms::PLATFORM_ANDROID)) {
                                            $server_key = $value['android_server_key'];
                                        } elseif ($device_type == strtolower(AppPlatforms::PLATFORM_IOS)) {
                                            $server_key = $value['ios_server_key'];
                                        } elseif ($device_type == strtolower(AppPlatforms::PLATFORM_WEB)) {
                                            $server_key = $value['web_server_key'];
                                        }
                                        else{
                                            $server_key = $value['android_server_key'];
                                        }
                                        //dump($device_type, $server_key);

                                        $notification_id=[];
                                        foreach($value['device_token'] as $tokens){
                                            $device_tokens[] = $tokens;
                                            $_notify = [];
                                            $_notify['device_token'] = $tokens;
                                            $_notify['payload'] = \GuzzleHttp\json_encode($payload_sandbox);
                                            $_notify['message'] = $html_content;
                                            $_notify['platform'] = $device_type;
                                            $notify_id = Notification::create($_notify)->id;
                                            $notification_id[] = $notify_id;

                                            $payload_sandbox['data']['view_link'] = config('engagement.url.inappview') . "notification/" . $notify_id;
                                            $payload_data['data']['view_link'] = config('engagement.url.inappview') . "notification/" . $notify_id;
                                            //$payload_data['data']['view_link'] = "https://www.yahoo.com";

                                            $_notify_ = Notification::find($notify_id);
                                            if($_notify_){
                                                $_notify_->payload = \GuzzleHttp\json_encode($payload_sandbox);
                                                $_notify_->save();
                                            }
                                        }
                                        //dump($notification);
                                        //dump($payload_data);
                                    }

                                    \Artisan::call('config:cache');
                                    \Config::set('fcm.http.server_key', $server_key);

                                    if(isset($device_tokens) && count($device_tokens) > 0 ){

                                        $notifications = new NotificationController($device_tokens, $notification, $payload_data, $server_key, $content_available);
                                        $response[] = $notifications->sendNotification();

                                        if(isset($response) && count($response) > 0){
                                            $number_success = (isset($response[0]['numberSuccess'])) ? $response[0]['numberSuccess'] : 0;
                                            $numberFailure = (isset($response[0]['numberFailure'])) ? $response[0]['numberFailure'] : 0;
                                            if((int)$number_success > 0){
                                                foreach($notification_id as $notify){
                                                    $_notify = Notification::find($notify);
                                                    $_notify->sent_at = Carbon::now();
                                                    $_notify->sent = 1;
                                                    $_notify->save();

                                                    $notify_log=[];
                                                    $notify_log['notification_id'] = $notify;
                                                    $notify_log['status'] = 'Success';
                                                    $notify_log['message'] = 'Notification Send Successfully.';
                                                    NotificationsLog::create($notify_log);
                                                }
                                                $_response[] = array(
                                                    'status' => AppStatusCodes::HTTP_OK,
                                                    'error' => 'Notification send successfully.',
                                                    'message' => 'Notification send successfully.'
                                                );
                                            }
                                            elseif( (isset($response[0]['tokensToDelete']))){
                                                $tokensToDelete = (isset($response[0]['tokensToDelete'])) ? $response[0]['tokensToDelete'] : [];
                                                $result=CommonHelper::updateDeviceToken($tokensToDelete,[
                                                    "app_group_id" => $data['app_group_id'],
                                                    'is_revoked'=>'1',
                                                    'status'=>'0',
                                                    'deleted_at' => Carbon::now()
                                                ]);
                                                $_response[] = array(
                                                    'status' => AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                                                    'error' => 'Notification not send.',
                                                    'message' => 'Notification not send.'
                                                );
                                            }
                                            if( (int)$numberFailure == count($device_tokens) ){
                                                foreach($notification_id as $notify){
                                                    $notify_log=[];
                                                    $notify_log['notification_id'] = $notify;
                                                    $notify_log['status'] = 'error';
                                                    $notify_log['message'] = 'Notification not send.';
                                                    NotificationsLog::create($notify_log);
                                                }
                                                $_response[] = array(
                                                    'status' => AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                                                    'error' => 'Notification not send.',
                                                    'message' => 'Notification not send.'
                                                );
                                            }
                                        }
                                    }
                                }
                            } elseif ($data['type'] == Campaign::CAMPAIGN_EMAIL_CODE) {
                                foreach ($_app_users as $key => $_user_tokens) {
                                    $notification = new Notifications\SendEmail($user, $_user_tokens);
                                    $_response[] = $notification->send();
                                }
                            }
                        }
                    }
                }
            }
            if(empty($response)){
                if($data['type'] != Campaign::CAMPAIGN_EMAIL_CODE){
                    $_response[] = array(
                        'status' => AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                        'error' => 'No valid token found.',
                        'message' => 'No valid token found.'
                    );
                }
            }
            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $_response,
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
}