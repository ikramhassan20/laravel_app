<?php

namespace App\Components;

use App\AppGroup;
use App\Apps;
use App\AppUsers;
use App\CampaignCapRule;
use App\Helpers\CampaignCappingHelper;
use App\Helpers\CommonHelper;
use App\Jobs\EmailJobWorker;
use App\Jobs\PushJobWorker;
use App\User;
use App\AppUserTokens;
use App\Language;
use App\Cache\CampaignTrackingCache;
use App\Cache\CampaignTranslationCache;
use App\Campaign;
use App\CampaignRateLimitRules;
use App\CampaignTracking;
use App\CampaignVariant;
use App\Lookup;
use App\Queue;
use App\Translation;
use Composer\Util\Platform;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\URL;

/**
 * Class CampaignDispatchProcess
 * @package App\Components
 * @todo process campaign dispatcher
 */
class CampaignDispatchProcess
{
    /**
     * process campaign dispatcher implementation
     *
     * @param array $campaign_segment_rows
     * @param int $campaign_id
     *
     * @return string $response
     */
    public static function processDispatcher($campaign_segment_rows, $campaign_id)
    {
        // getting campaign general info
        $_campaign_general = Campaign::find($campaign_id);
        $campaign_status = (isset($_campaign_general->status)) ? $_campaign_general->status : 'draft';
        $campaign_end_date = (isset($_campaign_general->end_time)) ? strtotime($_campaign_general->end_time) : '';

        if ($campaign_status != 'active') {
            $error = 'Campaign is not active.';
            Log::error($error);

            // return error response
            return $response = json_encode([
                'status' => AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                'error' => 'error',
                'data' => [$error]
            ],true);
        }

        $now = strtotime(date("Y-m-d h:i:s"));
        if($now > $campaign_end_date && $campaign_end_date != ""){
            $error = 'Campaign is expired.';
            //Log::error($error);

            // return error response
            /*return $response = json_encode([
                'status' => AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                'error' => 'error',
                'data' => [$error]
            ],true);*/
        }

        // getting app group info
        $app_group_id = (isset($_campaign_general->app_group_id)) ? $_campaign_general->app_group_id : "";
        $_app_group = AppGroup::find($app_group_id);
        $company_id = (isset($_app_group->company_id)) ? $_app_group->company_id : "";

        // apply validation for a valid company
        $_company = User::where('id', '=', $company_id)
            ->where('is_active', '=', '1')
            ->first();

        if (!isset($_company)) {
            $error = 'Company is not valid for the campaign.';
            Log::error($error);

            // return error response
            return $response = json_encode([
                'status' => AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                'error' => 'error',
                'data' => [$error]
            ],true);
        }

        // campaign other info
        $campaign_code = (isset($_campaign_general->code)) ? $_campaign_general->code : "";
        $campaign_type = (isset($_campaign_general->campaign_type)) ? strtolower($_campaign_general->campaign_type) : "";
        $subject = (isset($_campaign_general->subject)) ? $_campaign_general->subject : "";
        $from_email = (isset($_campaign_general->from_email)) ? $_campaign_general->from_email : "";
        $from_name =  (isset($_campaign_general->from_name)) ? $_campaign_general->from_name : "";
        $campaign_start_time = (isset($_campaign_general->start_time)) ? $_campaign_general->start_time : "";
        $campaign_delivery_control = (isset($_campaign_general->delivery_control)) ? $_campaign_general->delivery_control : "0";
        $campaign_priority = (isset($_campaign_general->priority)) ? $_campaign_general->priority : Campaign::PRIORITY_MEDIUM;
        $campaign_delivery_type = (isset($_campaign_general->delivery_type)) ? $_campaign_general->delivery_type : Campaign::DELIVERY_TYPE_ACTION;
        $campaign_capping = (isset($_campaign_general->capping)) ? $_campaign_general->capping : "0";

        // set campaign dispatch date time in UTC
        //$timestamp = date("Y-m-d h:i:s");
        //$campaign_dispatch_date = Carbon::createFromFormat('Y-m-d H:i:s', $timestamp, 'Asia/Dubai');
        //$campaign_dispatch_date->setTimezone('UTC');
        $campaign_dispatch_date = Carbon::parse(Carbon::now())->setTimezone("UTC")->format('Y-m-d');

        $_apps = Apps::where(['app_group_id' => $app_group_id])->first();
        $app_logo = (isset($_apps->logo)) ? $_apps->logo : "";

        // getting variants distribution on provided users
        $variants = DistributionVariants::distribution($campaign_segment_rows, $campaign_id);

        if (!isset($variants)) {
            $error = 'Distribution variant not found.';
            Log::error($error);

            // return error response
            return $response = json_encode([
                'status' => AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                'error' => 'error',
                'data' => [$error]
            ], true);
        }

        // apply rate limit and store variant wise data in log table


        // apply validation of available distribution variants
        $variants_tokens = VariantsValidation::process($variants, $app_group_id, $campaign_type);

        // clean variants memory
        unset($variants);

        if (!isset($variants_tokens)) {
            $error = 'No App user token(s) found.';
            Log::error($error);

            // return error response
            return $response = json_encode([
                'status' => AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                'error' => 'error',
                'data' => [$error]
            ], true);
        }

        $response=[];$email_array=[];
        if (isset($variants_tokens) && sizeof($variants_tokens) > 0) {

            // loop through each app user token id object
            $payload_params=[];$errors=[]; $variant_code=1;
            foreach ($variants_tokens as $key => $_variant_tokens) {

                if(empty($_variant_tokens['row_ids'])){
                    $error = "No App user token(s) found.";
                    Log::error($error);
                    $errors[] = $error;
                    continue;
                }

                if (isset($_variant_tokens['row_ids']) && isset($_variant_tokens['id'])) {

                    $variant_id = $_variant_tokens['id'];
                    if($campaign_type == Campaign::CAMPAIGN_EMAIL_CODE && count($_variant_tokens['row_ids']) > 0 ){
                        $token_id = [];
                        $groups_users_rows = $_variant_tokens['row_ids'];
                        foreach($groups_users_rows as $key=>$tokens){
                            $row_id = $tokens[0]->row_id;
                            $token_id[$row_id][] = $tokens[0];
                            //dump($row_id, $token_id);
                        }
                        unset($_variant_tokens['row_ids']);
                        $_variant_tokens['row_ids'] = $token_id;
                    }

                    // getting all app user rows
                    $groups_users_rows = $_variant_tokens['row_ids'];
                    foreach($groups_users_rows as $key => $user_rows) {

                        $row_id = $key;
                        if ($row_id == "") {
                            $error = "App user row id not found.";
                            Log::error($error);
                            $errors[] = $error;
                            continue;
                        }

                        // get all language codes from database
                        $language_codes = [];
                        $language = Language::where('code', '!=', '')->get();
                        foreach ($language as $code) {
                            $language_codes[$code->code] = [];
                        }
                        //Log::info("Language codes: " . \GuzzleHttp\json_encode($language_codes));

                        // grouping all codes in languages based
                        if (count($language_codes) > 0 && count($user_rows) > 0) {
                            foreach ($user_rows as $key => $val) {
                                $user_lang = (isset($val->apps_users_tokens->lang)) ? $val->apps_users_tokens->lang : "en";
                                if (isset($language_codes[$user_lang])) {
                                    $language_codes[$user_lang][] = $val;
                                }
                            }
                            //Log::info("User Language codes: " . \GuzzleHttp\json_encode($language_codes));
                        }

                        if (isset($language_codes) && count($language_codes) > 0) {
                            foreach ($language_codes as $key => $app_users) {

                                // app user language
                                $app_user_lang = (isset($key)) ? $key : "en";

                                /*if($app_user_lang == 'en' and !empty($app_users)){
                                    Log::info("Key: \"". $app_user_lang ."\" :codes:" . \GuzzleHttp\json_encode($app_users));
                                }
                                elseif($app_user_lang == 'ar' and !empty($app_users)){
                                    Log::info("Key: \"". $app_user_lang ."\" :codes:" . \GuzzleHttp\json_encode($app_users));
                                }
                                elseif($app_user_lang == 'el' and !empty($app_users)){
                                    Log::info("Key: \"". $app_user_lang ."\" :codes:" . \GuzzleHttp\json_encode($app_users));
                                }
                                elseif($app_user_lang == 'zh' and !empty($app_users)){
                                    Log::info("Key: \"". $app_user_lang ."\" :codes:" . \GuzzleHttp\json_encode($app_users));
                                }*/

                                if(isset($app_users) && count($app_users) > 0){

                                    // getting user email
                                    $to_email = (isset($app_users[0]->email)) ? $app_users[0]->email : "";
                                    $user_id = (isset($app_users[0]->user_id)) ? $app_users[0]->user_id : "";
                                    $app_user_token_id = (isset($app_users[0]->apps_users_tokens->id)) ? $app_users[0]->apps_users_tokens->id : "";
                                    $app_user_app_id = (isset($app_users[0]->apps_users_tokens->app_id)) ? $app_users[0]->apps_users_tokens->app_id : "";
                                    $app_user_device_type = (isset($app_users[0]->apps_users_tokens->device_type)) ? $app_users[0]->apps_users_tokens->device_type : strtolower(AppPlatforms::PLATFORM_IOS);
                                    $app_logo = CommonHelper::getAppIcon($app_user_app_id, $app_group_id, $app_user_device_type);
                                    //dump($to_email, $user_id, $app_user_token_id, $app_user_device_type);

                                    // when campaign has enabled delivery control
                                    $delivery_control = true;
                                    if ((int)$campaign_delivery_control > 0) {

                                        $field = Campaign::CAMPAIGN_EMAIL_CODE;
                                        //$delivery_control = false;
                                        if (strtolower($campaign_type) == Campaign::CAMPAIGN_PUSH_CODE) {
                                            $field = 'device_key';
                                        } elseif (strtolower($campaign_type) == Campaign::CAMPAIGN_INAPP_CODE) {
                                            $field = 'firebase_key';
                                        }

                                        // apply delivery control
                                        $delivery_control = (boolean)CampaignDeliveryControl::applyDeliveryControl($campaign_id, $row_id, $app_user_lang, $variant_id);
                                        //if ($delivery_control === false) {
                                        //    Log::info("Delivery control not applied.");
                                        //    continue;
                                        //}
                                    }

                                    /*$capping_control = true;
                                    if((int)$campaign_capping > 0){
                                        $cap_type = CampaignCappingHelper::cappingType($campaign_type);
                                        $cap_rule = CampaignCapRule::where('app_group_id', '=',$app_group_id)
                                            ->where('campaign_type', '=', $campaign_type)
                                            ->where('deleted_at', null)
                                            ->first();

                                        if(!empty($cap_type) && !empty($cap_rule)){
                                            foreach ($app_users as $key => $campaign_row) {
                                                $deliverAgain = CampaignCappingHelper::cappingEnabled($_campaign_general, $cap_rule, $row_id, $app_user_lang, $variant_id);
                                                if ($deliverAgain === true) {
                                                    unset($app_users[$key]);
                                                }
                                            }
                                        }
                                    }*/

                                    // continue creating payload when delivery control is not applied
                                    if($delivery_control) { //  || $capping_control

                                        // getting campaign variants info
                                        $_variant = CampaignVariant::find($variant_id);
                                        if (isset($_variant)) {
                                            // getting lookup info
                                            $_lookup = Lookup::find($_variant->position_id);
                                            $position = (isset($_lookup->code)) ? strtolower($_lookup->code) : "top";

                                            // getting lookup info
                                            $_lookup_type = Lookup::find($_variant->message_type_id);
                                            $_lookup_code = strtolower($_lookup_type->code);
                                            if($_lookup_code == 'full_screen'){
                                                $_lookup_code = str_replace("_", " ", $_lookup_code);
                                            }
                                            $message_type = (isset($_lookup_code)) ? str_replace("dialog", "dialogue", $_lookup_code) : "dialogue";

                                            // getting lookup info
                                            $_lookup_platform = Lookup::find($_variant->platform_id);
                                            $_platform = (isset($_lookup_platform->code)) ? strtolower($_lookup_platform->code) : "ios";

                                            // getting template translations info
                                            $template_title = "";
                                            $template_content = "";
                                            $message = "";
                                            $action_type = "";
                                            $action_value = "";
                                            $action_type2 = "";
                                            $action_value2 = "";
                                            $background = "#FFFFFF";

                                            $language = Language::where('code', '=', $app_user_lang)->first();
                                            $language_id = (isset($language->id)) ? $language->id : '1';

                                            // getting template content from cache
                                            $_template = CampaignTranslationCache::getCampaignTranslationCache($app_group_id, $campaign_id, $language_id, $_variant->id);
                                            if($_template==""){
                                                // getting template content from cache
                                                $language_id = 1;
                                                $_template = CampaignTranslationCache::getCampaignTranslationCache($app_group_id, $campaign_id, $language_id, $_variant->id);
                                            }

                                            if (isset($_template)) {
                                                $template_title = (isset($_template->templateInfo->title)) ? $_template->templateInfo->title : "";

                                                if ($campaign_type == Campaign::CAMPAIGN_PUSH_CODE) {
                                                    // push campaign image as app logo
                                                    $app_logo = (isset($_template->templateInfo->imgUrl)) ? $_template->templateInfo->imgUrl : "";
                                                    $template_content = (isset($_template->templateInfo->message)) ? $_template->templateInfo->message : "";
                                                    $action_value = (isset($_template->templateInfo->action1->value)) ? $_template->templateInfo->action1->value : "";
                                                    $action_value2 = (isset($_template->templateInfo->action2->value)) ? $_template->templateInfo->action2->value : "";
                                                } else {
                                                    $message = (isset($_template->templateInfo->message)) ? $_template->templateInfo->message : "";
                                                    $template_content = (isset($_template->templateInfo->template)) ? $_template->templateInfo->template : "";
                                                    $action_value = (isset($_template->templateInfo->action1->label)) ? $_template->templateInfo->action1->label : "";
                                                    $action_value2 = (isset($_template->templateInfo->action2->label)) ? $_template->templateInfo->action2->label : "";

                                                }
                                                if(strtolower($campaign_type) == Campaign::CAMPAIGN_INAPP_CODE){
                                                    $background = (isset($_template->templateInfo->design->background->color)) ? $_template->templateInfo->design->background->color : "";
                                                }

                                                $action_type = (isset($_template->templateInfo->action1->type->name)) ? $_template->templateInfo->action1->type->name : "";
                                                $action_type2 = (isset($_template->templateInfo->action2->type->name)) ? $_template->templateInfo->action2->type->name : "";
                                            }

                                            // apply attribute on message
                                            $template_title = MessageFormatter::apply_attribute($row_id, $company_id, $template_title);
                                            $template_content = MessageFormatter::apply_attribute($row_id, $company_id, $template_content);
                                            if ($message != "") {
                                                $message = MessageFormatter::apply_attribute($row_id, $company_id, $message);
                                            }
                                            //$template_title="";$template_content = "";
                                            if ($template_title == "" && $template_content == "") {
                                                $error = "Template title and content not found.";
                                                Log::error($error);
                                                $errors[] = $error;
                                                continue;
                                            }

                                            /*******************************************************************************/
                                            // Generating payload for Campaign In-app and Push
                                            /*******************************************************************************/
                                            $payload = [];
                                            if ($campaign_type == Campaign::CAMPAIGN_INAPP_CODE || $campaign_type == Campaign::CAMPAIGN_PUSH_CODE) {

                                                /*******************************************************************/
                                                // building app user tokens
                                                /*******************************************************************/
                                                $devices = [];
                                                foreach ($app_users as $key => $app_user_token_object) {

                                                    // getting app user tokens info
                                                    $device_type = (isset($app_user_token_object->apps_users_tokens->device_type)) ?
                                                        strtolower($app_user_token_object->apps_users_tokens->device_type) :
                                                        strtolower(AppPlatforms::PLATFORM_IOS);

                                                    // grouping app user tokens based on device types
                                                    if ($device_type == strtolower(AppPlatforms::PLATFORM_IOS) &&
                                                        ($_platform == strtolower(AppPlatforms::PLATFORM_IOS) || $_platform == strtolower(AppPlatforms::PLATFORM_UNIVERSAL) )
                                                    ){
                                                        $devices[$device_type][] = $app_user_token_object;
                                                    } elseif (strtolower($device_type) == strtolower(AppPlatforms::PLATFORM_ANDROID) &&
                                                        ($_platform == strtolower(AppPlatforms::PLATFORM_ANDROID) || $_platform == strtolower(AppPlatforms::PLATFORM_UNIVERSAL) )
                                                    ) {
                                                        $devices[$device_type][] = $app_user_token_object;
                                                    } elseif (strtolower($device_type) == strtolower(AppPlatforms::PLATFORM_WEB) &&
                                                        ($_platform == strtolower(AppPlatforms::PLATFORM_WEB) || $_platform == strtolower(AppPlatforms::PLATFORM_UNIVERSAL) )
                                                    ) {
                                                        $devices[$device_type][] = $app_user_token_object;
                                                    }
                                                }

                                                // loop through each device types
                                                $tokens_data = [];
                                                $_campaign_tracking = [];
                                                foreach ($devices as $key => $app_users) {

                                                    // device type
                                                    $device_type = $key;
                                                    //print_r($app_users);

                                                    $server_key = "";
                                                    $device_tokens = [];
                                                    $tracking_keys = [];
                                                    if (isset($app_users) && count($app_users) > 0) {
                                                        foreach ($app_users as $key => $app_user_token_object) {

                                                            $app_group_id = (isset($app_user_token_object->app_group_id)) ? $app_user_token_object->app_group_id : "";
                                                            $app_id = (isset($app_user_token_object->apps_users_tokens->app_id)) ? $app_user_token_object->apps_users_tokens->app_id : "";

                                                            $server_key = CommonHelper::getAppServerKey($app_id, $app_group_id, $device_type);
                                                            //$_apps = Apps::where(['app_group_id' => $app_group_id])->first();
                                                            //$server_key = (isset($_apps->firebase_api_key)) ? $_apps->firebase_api_key : "";

                                                            if ($server_key == "") {
                                                                $error = 'Server Key not found. ';
                                                                Log::info($error);
                                                                $errors[] = $error;
                                                                continue;
                                                            }

                                                            // getting app user tokens info
                                                            $_app_user_token = (isset($app_user_token_object->apps_users_tokens)) ? $app_user_token_object->apps_users_tokens : "";

                                                            $app_user_token_id = (isset($_app_user_token->id)) ? $_app_user_token->id : "";
                                                            if ($app_user_token_id == "") {
                                                                $error = "App user token id not found.";
                                                                Log::info($error);
                                                                $errors[] = $error;
                                                                continue;
                                                            }

                                                            $device_token = (isset($_app_user_token->device_token)) ? $_app_user_token->device_token : "";
                                                            if ($device_token == "") {
                                                                $error = "Device token not found.";
                                                                Log::info($error);
                                                                $errors[] = $error;
                                                                continue;
                                                            }

                                                            // logic to build all track keys
                                                            $tracking_key = RandomString::generate();

                                                            $device_tokens[] = $device_token;
                                                            $tracking_keys[] = $tracking_key;

                                                            $_campaign_tracking[] = array(
                                                                'app_user_token_id' => $app_user_token_id,
                                                                'firebase_key' => $server_key,
                                                                'device_key' => "$device_token",
                                                                'device_type' => "$device_type",
                                                                'track_key' => $tracking_key
                                                            );
                                                        }

                                                        $tokens_data[] = array(
                                                            'server_key' => $server_key,
                                                            'device_type' => $device_type,
                                                            'device_token' => $device_tokens,
                                                            'tracking_key' => $tracking_keys,
                                                        );
                                                        //dd($tokens_data);
                                                    }
                                                }
                                                //dd($tokens_data);
                                                //dd($_campaign_tracking);
                                                /*******************************************************************/

                                                $_track_key = (isset($tokens_data[0]['tracking_key'][0])) ? $tokens_data[0]['tracking_key'][0] : "";
                                                $campaign_inapp_view = config('engagement.url.inappview') . $_track_key."?identifier=campaign_url";
                                                //dd($campaign_inapp_view);

                                                /*if ($campaign_type == Campaign::CAMPAIGN_INAPP_CODE) {

                                                    if (strtolower($action_type) != 'deep link' && strtolower($action_type) != 'close' ) {
                                                        $template_content = str_replace($action_value,
                                                            url('') . '/trackLink?enc=' . base64_encode(Campaign::CAMPAIGN_INAPP_CODE . '/' . $campaign_id . '/' . $row_id . '/' . $action_value),
                                                            $template_content);

                                                    }

                                                    if (strtolower($action_type2) != 'deep link' && strtolower($action_type2) != 'close' && strtolower($action_type2) != '') {
                                                        $template_content = str_replace($action_value2,
                                                            url('') . '/trackLink?enc=' . base64_encode(Campaign::CAMPAIGN_INAPP_CODE . '/' . $campaign_id . '/' . $row_id . '/' . $action_value2),
                                                            $template_content);
                                                    }
                                                }*/

                                                // setting up auto close popup feature
                                                $auto_close = true;
                                                if($action_value != "" && $action_value2 != ""){
                                                    $auto_close = false;
                                                }

                                                if ($campaign_type == Campaign::CAMPAIGN_PUSH_CODE){
                                                    $message = $template_content;
                                                }

                                                $_payload_params = [
                                                    'title' => $template_title,
                                                    'body' => $message,
                                                    'background' => $background,
                                                    "app_group_id" => $app_group_id,
                                                    'campaign_code' => $campaign_code,
                                                    'campaign_id' => $campaign_id,
                                                    'app_logo' => $app_logo,
                                                    'message_type' => $message_type,
                                                    'row_id' => $row_id,
                                                    'user_id' => $user_id,
                                                    'campaign_type' => $campaign_type,
                                                    'position' => $position,
                                                    'priority' => $campaign_priority,
                                                    "auto_close" => $auto_close,
                                                    "action_type" => $action_type,
                                                    "action_value" => $action_value,
                                                    "language" => $app_user_lang,
                                                    "variant_id" => $variant_id,
                                                    "tokens_data" => $tokens_data,
                                                    'campaign_inapp_view' => $campaign_inapp_view,
                                                    'campaign_start_time' => $campaign_start_time,
                                                    'campaign_dispatch_date' => $campaign_dispatch_date,
                                                    'is_board' => false
                                                ];

                                                $job_name = 'PushJobWorker';
                                                // generating campaign payload for inapp and push types
                                                try {
                                                    $payload = CampaignPayload::generateInAppPushPayload($_payload_params);
                                                } catch (\Exception $exception) {
                                                    $error = $exception->getMessage();
                                                    Log::error($error);
                                                    $errors[] = $error;
                                                }

                                                foreach ($_campaign_tracking as $key => $tracking) {
                                                    // process campaign tracking
                                                    $campaign_tracking = [];
                                                    $campaign_tracking['campaign_id'] = $campaign_id;
                                                    $campaign_tracking['row_id'] = $row_id;
                                                    $campaign_tracking['app_user_token_id'] = $tracking['app_user_token_id'];
                                                    $campaign_tracking['variant_id'] = $variant_id;
                                                    $campaign_tracking['language_id'] = $language_id;
                                                    $campaign_tracking['email'] = $to_email;
                                                    $campaign_tracking['firebase_key'] = $tracking['firebase_key'];
                                                    $campaign_tracking['device_key'] = $tracking['device_key'];
                                                    $campaign_tracking['device_type'] = $tracking['device_type'];
                                                    //$campaign_tracking['payload'] = \GuzzleHttp\json_encode($payload, true);
                                                    $campaign_tracking['payload'] = '';
                                                    $campaign_tracking['track_key'] = $tracking['track_key'];
                                                    $campaign_tracking['job'] = $job_name;
                                                    $campaign_tracking['status'] = 'added';
                                                    $campaign_tracking['sent'] = 0;
                                                    $campaign_tracking['viewed'] = 0;
                                                    //dd($campaign_tracking);
                                                    CampaignTracking::create($campaign_tracking);

                                                    // store campaign tracking contents into cache
                                                    $campaign_tracking = new CampaignTrackingCache();
                                                    $campaign_tracking->addCampaignTrackingCache($campaign_id, $row_id, $app_user_lang, $variant_id, $template_content);
                                                }

                                            } elseif ($campaign_type == Campaign::CAMPAIGN_EMAIL_CODE) {

                                                /*******************************************************************************/
                                                // Generating payload for Campaign Email Type
                                                /*******************************************************************************/

                                                /*$match = [];
                                                preg_match_all('/<a\s+(?:[^>]*?\s+)?href=(["\'])(.*?)\1/', $template_content, $match);
                                                if (isset($match[2])) {
                                                    foreach ($match[2] as $url) {
                                                        $url = htmlspecialchars_decode($url);
                                                        $template_content = str_replace($url,
                                                            url('') . '/trackLink?enc=' . base64_encode(Campaign::CAMPAIGN_EMAIL_CODE . '/' . $campaign_id . "/" . $row_id . '/' . $url),
                                                            $template_content);
                                                    }
                                                }*/

                                                $dom = new \DOMDocument();
                                                $dom->loadHTML(mb_convert_encoding($template_content, 'HTML-ENTITIES', 'UTF-8'));
                                                $anchorTags = $dom->getElementsByTagName('a');
                                                if(isset($anchorTags)) {
                                                    foreach ($anchorTags as $anchorTag) {
                                                        foreach ($anchorTag->attributes as $attribute) {
                                                            if ($attribute->nodeName == 'href') {
                                                                $anchorTag->setAttribute('href', url('') . '/trackLink?enc=' . base64_encode(Campaign::CAMPAIGN_EMAIL_CODE . '/' . $campaign_id . "/" . $row_id . '/' . $attribute->nodeValue));
                                                            }
                                                        }
                                                    }
                                                    $template_content = $dom->saveHTML();
                                                }

                                                // logic to build all track keys
                                                $tracking_key = RandomString::generate();

                                                // create tracking URL.
                                                $tracking_url = config('engagement.url.auth') . $tracking_key;
                                                $template_content .= "<img src=\"$tracking_url\" style='display: none;'>";

                                                /*$unsubscribe_url = route('unsubscribe-email') .'?enc='. base64_encode($tracking_key);
                                                try{
                                                    $unsubscribe_url = CommonHelper::fetchTinyUrl($unsubscribe_url);
                                                }
                                                catch (\Exception $exception){
                                                }*/

                                                if(strpos($template_content, '</body>')){
                                                    $template_content = str_replace( '</body>',
                                                        '<div class="social" style="border-bottom: 2px solid #f7f5f5; text-align: center; padding: 30px 0;">
    <a href="#" style="display:inline-block; vertical-align:top; margin-right:23px;" target="_blank"><img src="'.URL::to('/').'/images/twitter.png" alt="twitter" style="width:24px;"></a>
    <a href="#" style="display:inline-block; vertical-align:top; margin-right:23px;" target="_blank"><img src="'.URL::to('/').'/images/discord.png" alt="discord" style="width:24px;"></a>
    <a href="#" style="display:inline-block; vertical-align:top; margin-right:23px;" target="_blank"><img src="'.URL::to('/').'/images/instagram.png" alt="instagram" style="width:24px;"></a>
    <a href="#" style="display:inline-block; vertical-align:top; margin-right:23px;" target="_blank"><img src="'.URL::to('/').'/images/youtube.png" alt="youtube" style="width:24px;"></a>
</div><div style="font-size:14px;color:#a79d9d;padding:10px;margin:10px;text-align: center;">Don\'t want to receive further emails? 
                                                                    <a href="'. route('unsubscribe-email') .'?enc='. base64_encode($tracking_key) .'" target="_blank" >Unsubscribe here</a> 
                                                                </div></body>', $template_content);
                                                }
                                                else{
                                                    $template_content = $template_content .
                                                        '<div class="social" style="border-bottom: 2px solid #f7f5f5; text-align: center; padding: 30px 0;">
    <a href="#" style="display:inline-block; vertical-align:top; margin-right:23px;" target="_blank"><img src="'.URL::to('/').'/images/twitter.png" alt="twitter" style="width:24px;"></a>
    <a href="#" style="display:inline-block; vertical-align:top; margin-right:23px;" target="_blank"><img src="'.URL::to('/').'/images/discord.png" alt="discord" style="width:24px;"></a>
    <a href="#" style="display:inline-block; vertical-align:top; margin-right:23px;" target="_blank"><img src="'.URL::to('/').'/images/instagram.png" alt="instagram" style="width:24px;"></a>
    <a href="#" style="display:inline-block; vertical-align:top; margin-right:23px;" target="_blank"><img src="'.URL::to('/').'/images/youtube.png" alt="youtube" style="width:24px;"></a>
</div><div style="font-size:14px;color:#a79d9d;padding:10px;margin:10px;text-align: center;">Don\'t want to receive further emails? 
                                                                    <a href="'. route('unsubscribe-email') .'?enc='. base64_encode($tracking_key) .'" target="_blank" >Unsubscribe here</a> 
                                                                </div></body>';
                                                }

                                                // generate in-app view link
                                                $campaign_inapp_view = config('engagement.url.inappview') . $tracking_key."?identifier=campaign_url";
                                                //dd($campaign_inapp_view);

                                                $_payload_params = [
                                                    "app_group_id" => $app_group_id,
                                                    'campaign_code' => $campaign_code,
                                                    'campaign_id' => $campaign_id,
                                                    'tracking_key' => $tracking_key,
                                                    'row_id' => $row_id,
                                                    'user_id' => $user_id,
                                                    'campaign_type' => $campaign_type,
                                                    'campaign_inapp_view' => $campaign_inapp_view,
                                                    'template_content' => $template_content,
                                                    "language" => $app_user_lang,
                                                    "variant_id" => $variant_id,
                                                    'subject' => $subject,
                                                    'from_email' => $from_email,
                                                    'from_name' => $from_name,
                                                    'to_email' => $to_email,
                                                    'priority' => $campaign_priority,
                                                    'campaign_start_time' => $campaign_start_time,
                                                    'is_board' => false
                                                ];
                                                //dump($_payload_params);
                                                $job_name = 'EmailJobWorker';

                                                // generating campaign payload for email type
                                                try {
                                                    $payload = CampaignPayload::generateEmailPayload($_payload_params);
                                                } catch (\Exception $exception) {
                                                    $error = $exception->getMessage();
                                                    Log::error($error);
                                                    $errors[] = $error;
                                                }

                                                //dump($payload);

                                                if(!isset(  $email_array[ $_payload_params['to_email'] ] )) {

                                                    //dump("=", $email_array, "=");

                                                    // updating new array with saved emails
                                                    $email_array[ $_payload_params['to_email'] ] = true;

                                                    // create tracking table with payload and create tracking cache
                                                    // process campaign tracking
                                                    $campaign_tracking = [];
                                                    $campaign_tracking['campaign_id'] = $campaign_id;
                                                    $campaign_tracking['row_id'] = $row_id;
                                                    $campaign_tracking['app_user_token_id'] = $app_user_token_id; // ***
                                                    $campaign_tracking['variant_id'] = $variant_id;
                                                    $campaign_tracking['language_id'] = $language_id;
                                                    $campaign_tracking['email'] = $to_email;
                                                    $campaign_tracking['firebase_key'] = '';
                                                    $campaign_tracking['device_key'] = '';
                                                    $campaign_tracking['device_type'] = $app_user_device_type;
                                                    //$campaign_tracking['payload'] = \GuzzleHttp\json_encode($payload, true);
                                                    $campaign_tracking['payload'] = '';
                                                    $campaign_tracking['track_key'] = $tracking_key;
                                                    $campaign_tracking['job'] = $job_name;
                                                    $campaign_tracking['status'] = 'added';
                                                    $campaign_tracking['sent'] = 0;
                                                    $campaign_tracking['viewed'] = 0;
                                                    //dd($campaign_tracking);
                                                    CampaignTracking::create($campaign_tracking);

                                                    // store campaign tracking contents into cache
                                                    $campaign_tracking = new CampaignTrackingCache();
                                                    $campaign_tracking->addCampaignTrackingCache($campaign_id, $row_id, $app_user_lang, $variant_id, $template_content);
                                                }
                                            }

                                            $payload_params[] = ['payload' => $payload, 'variant_code' => 'variant'.$variant_code,'priority' => $campaign_priority, 'payload_interval' => array('payload_interval' => $campaign_start_time)];
                                        }
                                    }
                                    else{
                                        $error = "Delivery control applied.";
                                        Log::info($error);
                                        $errors[] = $error;
                                    }
                                }
                            }
                        }
                        //dd($payload_params);
                    }
                    $variant_code++;
                }
            }
            //dump(json_encode($payload_params));

            // skip duplicate emails only for email type campaigns
            if ($campaign_type == Campaign::CAMPAIGN_EMAIL_CODE) {
                // skip duplicate emails from data payload
                $payload_params = self::skip_duplicates($payload_params, ["payload", "data", "to_email"]);
            }

            if(!empty($errors)){
                $response = [
                    'status' => AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                    'error' => 'error',
                    'data' => $errors
                ];
            }
            elseif(!empty($payload_params)) {

                // rate limit applies only when campaign delivery type schedule
                if ($campaign_delivery_type == Campaign::DELIVERY_TYPE_SCHEDULE) {

                    // apply campaign rate limit
                    $campaign_rate_limit = CampaignRateLimitRules::where('campaign_id', '=', $campaign_id)->first();
                    //if (isset($campaign_rate_limit)) {
                    /**
                     * Fetched the rate limit rule, it will be integer
                     */
                    $rate_limit = (isset($campaign_rate_limit->rate_limit)) ? $campaign_rate_limit->rate_limit : "1";
                    /**
                     * Fetched the duration, it will be integer
                     */
                    $duration_value = (isset($campaign_rate_limit->duration_value)) ? $campaign_rate_limit->duration_value : "1000";
                    /**
                     * Fetched the duration_unit, it will be varchar
                     */
                    $duration_unit = (isset($campaign_rate_limit->duration_unit)) ? $campaign_rate_limit->duration_unit : "minutes";

                    $_payload = RateLimitingComponents::rateLimitingRules($payload_params, $rate_limit, $duration_value, $duration_unit);
                    for ($val = 0; $val < count($_payload); $val++) {
                        $payload_interval = (isset($_payload[$val]['payload']['data']['interval'])) ? $_payload[$val]['payload']['data']['interval'] : $_payload[$val]['payload']['data']['start_date'];
                        $_payload[$val]['payload_interval'] = $payload_interval;

                        $priority = (isset($_payload[$val]['priority'])) ? $_payload[$val]['priority'] : Campaign::PRIORITY_MEDIUM;
                        $variant_name = "";
                        $variant_code = (isset($_payload[$val]['variant_code'])) ? $_payload[$val]['variant_code'] : 'variant1';
                        if ($variant_code != "variant1") {
                            $variant_name = $variant_code;
                        }

                        if ($campaign_type == Campaign::CAMPAIGN_INAPP_CODE || $campaign_type == Campaign::CAMPAIGN_PUSH_CODE) {
                            // adding PushJobWorker Queue
                            PushJobWorker::dispatch($_payload[$val]['payload'])
                                ->onQueue("$campaign_type$priority$variant_name")
                                ->delay(Carbon::now());
                        } else {
                            // adding EmailJobWorker Queue
                            EmailJobWorker::dispatch($_payload[$val]['payload'])
                                ->onQueue("$campaign_type$priority$variant_name")
                                ->delay(Carbon::now());
                        }

                    }
                    $payload_params = $_payload;
                    //}
                }
                $response = [
                    'status' => AppStatusCodes::HTTP_OK,
                    'success' => 'success',
                    'data' => $payload_params
                ];
            }
        }
        // clean payload and variant tokens memory
        unset($email_array);
        unset($payload_params);
        unset($variants_tokens);

        // return response
        return \GuzzleHttp\json_encode($response, true);
    }

    /**
     * Identify duplication of emails from array and skip duplication
     *
     * @param array $array
     * @param array $path
     *
     * @return array $array
    */
    public static function skip_duplicates($array, $path){

        // empty initialize array
        $valueAsKeyHolder = [];

        // loop through each index of array
        for($i = 0; $i < sizeof($array); $i++){

            $getValue = $array[$i];

            // loop through each index of array
            foreach($path as $index){
                $getValue = $getValue[$index];
            }

            // skip duplication array index elements
            if(isset($valueAsKeyHolder[$getValue])){
                unset($array[$i]);
                $array = array_values($array);
                $i--;
            }
            else{
                $valueAsKeyHolder[$getValue] = true;
            }
        }

        // return response as array
        return $array;
    }
}