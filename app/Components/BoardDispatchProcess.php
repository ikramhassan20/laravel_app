<?php

namespace App\Components;

use App\AppGroup;
use App\Apps;
use App\Helpers\CommonHelper;
use App\Jobs\EmailJobWorker;
use App\Jobs\PushJobWorker;
use App\User;
use App\Cache\CampaignTranslationCache;
use App\Lookup;
use App\Queue;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Board;
use App\BoardVariant;
use App\BoardVariantStep;
use App\BoardTracking;
use App\Cache\OnceBoardRowIdsCache;
use Illuminate\Support\Facades\URL;

/**
 * Class BoardDispatchProcess
 * @package App\Components
 */
class BoardDispatchProcess
{
    /**
     * process campaign dispatcher implementation
     *
     * @param array $campaign_segment_rows
     * @param int $campaign_id
     *
     * @return string $response
     */
    public static function processDispatcher($board_segment_rows, $board_id)
    {
        // getting campaign general info
        //$_board_general = Board::find($board_id);
        $_board_general = Board::where('id', '=', $board_id)
            ->select('app_group_id', 'status', 'start_time', 'end_time', 'code', 'delivery_control', 'priority', 'delivery_type', 'capping', 'schedule_type', 'delivery_control_delay_value')
            ->first();

        $board_status = (isset($_board_general->status)) ? $_board_general->status : 'draft';
        $board_end_date = (isset($_board_general->end_time)) ? strtotime($_board_general->end_time) : '';

        // if board schedule Once, then null $board_end_date
        if($_board_general->schedule_type ==  Board::SCHEDULE_ONCE){

            // create Once Board Cache object and get rowIds from cache
            $objOnceBoardCache = new OnceBoardRowIdsCache();
            $onceBoardCache = $objOnceBoardCache->getOnceBoardRowIdsCache($_board_general->app_group_id, $board_id);
            if($onceBoardCache){
                // assign cache rowIds to borad segment rowIds
                //$board_segment_rows = $onceBoardCache;
            }
            else{
                // save cache rowIds in cache system
                $objOnceBoardCache->saveOnceBoardRowIdsCache($_board_general->app_group_id, $board_id, $board_segment_rows);
            }
        }

        if ($board_status != 'active') {
            $error = 'Board is not active.';
            Log::error($error);

            // return error response
            return $response = json_encode([
                'status' => AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                'error' => 'error',
                'data' => [$error]
            ],true);
        }

        $now = strtotime(date("Y-m-d h:i:s"));
        if($now > $board_end_date && $board_end_date != ""){
            $error = 'Board is expired.';
            Log::error($error);

            // return error response
            return $response = json_encode([
                'status' => AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                'error' => 'error',
                'data' => [$error]
            ],true);
        }

        // getting app group info
        $app_group_id = (isset($_board_general->app_group_id)) ? $_board_general->app_group_id : "";
        //$_app_group = AppGroup::find($app_group_id);
        $_app_group = AppGroup::where('id', '=', $app_group_id)->select('company_id')->first();
        $company_id = (isset($_app_group->company_id)) ? $_app_group->company_id : "";

        // apply validation for a valid company
        $_company = User::where('id', '=', $company_id)
            ->where('is_active', '=', '1')
            ->select('id')
            ->first();

        if (!isset($_company)) {
            $error = 'Company is not valid for the board.';
            Log::error($error);

            // return error response
            return $response = json_encode([
                'status' => AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                'error' => 'error',
                'data' => [$error]
            ],true);
        }

        // board other info
        $board_code = (isset($_board_general->code)) ? $_board_general->code : "";
        $board_start_time = (isset($_board_general->start_time)) ? $_board_general->start_time : "";
        $board_delivery_control = (isset($_board_general->delivery_control)) ? $_board_general->delivery_control : "0";
        $board_delivery_control_delay_value = (isset($_board_general->delivery_control_delay_value)) ? $_board_general->delivery_control_delay_value : "0";
        $board_priority = (isset($_board_general->priority)) ? $_board_general->priority : Board::PRIORITY_MEDIUM;
        $board_delivery_type = (isset($_board_general->delivery_type)) ? $_board_general->delivery_type : Board::DELIVERY_TYPE_SCHEDULE;
        $board_capping = (isset($_board_general->capping)) ? $_board_general->capping : "0";

        // set campaign dispatch date time in UTC
        //$timestamp = date("Y-m-d h:i:s");
        //$campaign_dispatch_date = Carbon::createFromFormat('Y-m-d H:i:s', $timestamp, 'Asia/Dubai');
        //$campaign_dispatch_date->setTimezone('UTC');
        $board_dispatch_date = Carbon::parse(Carbon::now())->setTimezone("UTC")->format('Y-m-d');

        $_apps = Apps::where(['app_group_id' => $app_group_id])->select('logo')->first();
        $app_logo = (isset($_apps->logo)) ? $_apps->logo : "";

        // get all languages array from db
        $languages = \App\Language::getAllLanguages();

        // getting variants distribution on provided users
        $variants = DistributionVariants::distribution_board_variants($board_segment_rows, $board_id);
        /*echo 'variants';
        print_r($variants);*/

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

        // apply validation of available distribution variants
        $variants_tokens = BoardVariantsValidation::process($variants, $app_group_id);
        /*echo 'variant user tokens';
        print_r($variants_tokens);*/

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


        $response = [];
        $email_array = [];
        if(isset($variants_tokens) && count($variants_tokens) > 0){

            $payload_params = [];
            $errors = [];
            $variant_code = 1;

            // iterate through each variants and its row_ids
            foreach ($variants_tokens as $variant_users_data) {

                /*if(empty($variant_users_data['row_ids'])){
                    $error = "No App user token(s) found.";
                    Log::error($error);
                    $errors[] = $error;
                    continue;
                }*/

                if(isset($variant_users_data['row_ids']) && isset($variant_users_data['id'])){

                    // get variant info
                    $variant_id = $variant_users_data['id'];
                    $variant_type = $variant_users_data['variant_type'];
                    $variant_from_name = $variant_users_data['from_name'];
                    $variant_from_email = $variant_users_data['from_email'];
                    $variant_subject = $variant_users_data['subject'];

                    // remove multiple user tokens in case of Email Type
                    if(strtolower($variant_type) == strtolower(BoardVariant::VARIANT_EMAIL_CODE) && count($variant_users_data['row_ids']) > 0 ){
                        $token_id = [];
                        $groups_users_rows = $variant_users_data['row_ids'];
                        foreach($groups_users_rows as $key=>$tokens){
                            $row_id = $tokens[0]->row_id;
                            $token_id[$row_id][] = $tokens[0];
                            //dump($row_id, $token_id);
                        }
                        unset($variant_users_data['row_ids']);
                        $variant_users_data['row_ids'] = $token_id;
                    } // end of if

                    //print_r($variant_users_data);

                    // iterate through each row_id/app_user, and prepare payload
                    foreach($variant_users_data['row_ids'] as $row_id => $user_tokens_data) {

                        if ($row_id == "") {
                            $error = "App user row id not found.";
                            Log::error($error);
                            $errors[] = $error;
                            continue;
                        }

                        // iterate through each user token
                        if(count($user_tokens_data) > 0){
                            $server_key = "";
                            /*$device_tokens = [];
                            $tracking_keys = [];
                            $tokens_data = [];
                            $_board_tracking = [];*/
                            foreach($user_tokens_data as $userObj){
                                //print_r($userObj);
                                $device_tokens = [];
                                $tracking_keys = [];
                                $tokens_data = [];
                                $_board_tracking = [];

                                // user language code logic
                                $userLanguageCode = (isset($userObj->apps_users_tokens->lang) && !empty($userObj->apps_users_tokens->lang) ? $userObj->apps_users_tokens->lang : "en");
                                $userLanguageId = 1;
                                if(array_key_exists($userLanguageCode, $languages)){
                                    $userLanguageId = $languages[$userLanguageCode];
                                }
                                else{
                                    $userLanguageId = 1;
                                }

                                // get user info from userobj
                                $to_email = (isset($userObj->email)) ? $userObj->email : "";
                                $user_id = (isset($userObj->user_id)) ? $userObj->user_id : "";
                                $app_user_token_id = (isset($userObj->apps_users_tokens->id)) ? $userObj->apps_users_tokens->id : "";
                                $app_user_app_id = (isset($userObj->apps_users_tokens->app_id)) ? $userObj->apps_users_tokens->app_id : "";
                                $app_user_device_type = (isset($userObj->apps_users_tokens->device_type)) ? strtolower($userObj->apps_users_tokens->device_type) : strtolower(AppPlatforms::PLATFORM_IOS);
                                $app_user_group_id = (isset($userObj->app_group_id)) ? $userObj->app_group_id : "";
                                $app_user_device_token = (isset($userObj->apps_users_tokens->device_token)) ? $userObj->apps_users_tokens->device_token : "";
                                $app_logo = CommonHelper::getAppIcon($app_user_app_id, $app_group_id, $app_user_device_type);


                                // check that BoardUserTrackingCache Exist or not against row_id
                                // if not exist, then its first time sending case and we will dispatch first step of each variant
                                /*$boardUserTrackingCache = new \App\Cache\BoardUserTrackingCacheV2();
                                $boardUserTracking = $boardUserTrackingCache->getBoardUserTrackingCache($board_id, $row_id);*/
                                $boardUserTracking = \App\Cache\BoardUserTrackingCache::getBoardUserTrackingCache($board_id, $row_id);
                                if($boardUserTracking){
                                    $variantId = $boardUserTracking->variant_id;
                                    //$variantStepId = BoardVariantStep::getVariantNextStepId($variantId, $boardUserTracking->variant_step_id);
                                    $variantStepIndex = $boardUserTracking->variant_step_index + 1;
                                    $variantStepId = (isset($variant_users_data['steps'][$variantStepIndex]) ? $variant_users_data['steps'][$variantStepIndex ]['id'] : NULL);
                                    // enable delivery control check true, so that we can check delivery controls
                                    $enableDeliveryControlCheck = true;
                                    $delivery_control = true;
                                    Log::info('here1 - in cache');

                                }
                                else{
                                    $variantId = $variant_users_data['id'];
                                    $variantStepId = $variant_users_data['steps'][0]['id'];
                                    $variantStepIndex = 0;
                                    // enable delivery control check false, first time sending case
                                    $enableDeliveryControlCheck = false;
                                    $delivery_control = true;
                                }

                                // if variantId and variantStepId is not null, then we proceed
                                // possibilty that $variantStepId is NULL, in case of no more steps remain to send
                                if(isset($variantId) && isset($variantStepId)){
                                    Log::info('here2 - isset variantId and StepId');
                                    // Apply board level delivery control, if enabled in board and $enableDeliveryControlCheck is true
                                    if($enableDeliveryControlCheck){
                                        Log::info('here3 - enable delivery control');

                                        // main board level delivery control
                                        $main_delivery_control = true;
                                        if ((int)$board_delivery_control > 0 && (int)$board_delivery_control_delay_value > 0) {
                                            Log::info('here4 - in main delivery control');
                                            // apply delivery control
                                            $main_delivery_control = (boolean)BoardDeliveryControl::applyDeliveryControl($board_id, $row_id);
                                            Log::info($main_delivery_control);
                                        }

                                        Log::info('here5 - after main delivery control');

                                        // variant step level delivery control
                                        $variant_delivery_control = (boolean)BoardDeliveryControl::applyVariantStepDeliveryControl($board_id, $row_id, $variantStepId);
                                        Log::info($variant_delivery_control);

                                        Log::info('here6 - after variant delivery control');

                                        // if both main and step level delivery control are true, then we will proceed to send msg
                                        if($main_delivery_control && $variant_delivery_control) {
                                            $delivery_control = true;
                                            Log::info('here7 - Deliver control is true');
                                        }
                                        else{
                                            $delivery_control = false;
                                            Log::info('here8 - Deliver control is fasle');
                                        }

                                    }

                                    // if delivery control is true, then proceed to prepare payload
                                    if($delivery_control){

                                        Log::info('here9 - In Deliver control condition');
                                        /*echo "Innnnnnnnnnnnnnnnnnnnnnnnnn";
                                        echo "\n";
                                        echo 'Row_id '. $row_id;
                                        echo "\n";
                                        echo 'variant_id '. $variantId;
                                        echo "\n";
                                        echo 'variant_step_id '. $variantStepId;
                                        echo "\n";
                                        echo "\n";
                                        echo "\n";
                                        echo "\n";
                                        exit;*/

                                        //$variantStep = BoardVariantStep::find($variantStepId);
                                        $variantStep = BoardVariantStep::where('id', '=', $variantStepId)
                                            ->select('position_id', 'message_type_id', 'platform_id')
                                            ->first();

                                        if($variantStep){

                                            // get position from lookup table
                                            //$_lookup = Lookup::find($variantStep->position_id);
                                            $_lookup = Lookup::where('id', '=', $variantStep->position_id)->select('code')->first();
                                            $position = (isset($_lookup->code)) ? strtolower($_lookup->code) : "top";

                                            // get message type from lookup table
                                            //$_lookup_type = Lookup::find($variantStep->message_type_id);
                                            $_lookup_type = Lookup::where('id', '=', $variantStep->message_type_id)->select('code')->first();
                                            $_lookup_code = strtolower($_lookup_type->code);
                                            if($_lookup_code == 'full_screen'){
                                                $_lookup_code = str_replace("_", " ", $_lookup_code);
                                            }
                                            $message_type = (isset($_lookup_code)) ? str_replace("dialog", "dialogue", $_lookup_code) : "dialogue";

                                            // get platform from lookup table
                                            //$_lookup_platform = Lookup::find($variantStep->platform_id);
                                            $_lookup_platform = Lookup::where('id', '=', $variantStep->platform_id)->select('code')->first();
                                            $_platform = (isset($_lookup_platform->code)) ? strtolower($_lookup_platform->code) : "ios";

                                            // default values for template translations
                                            $template_title = "";
                                            $template_content = "";
                                            $message = "";
                                            $action_type = "";
                                            $action_value = "";
                                            $action_type2 = "";
                                            $action_value2 = "";
                                            $background = "#FFFFFF";

                                            // get template data/content from translation cache
                                            $_template = CampaignTranslationCache::getBoardTranslationCache($app_group_id, $board_id, $userLanguageId, $variantStepId);
                                            if($_template==""){
                                                $userLanguageId = 1;
                                                $_template = CampaignTranslationCache::getBoardTranslationCache($app_group_id, $board_id, $userLanguageId, $variantStepId);
                                            }

                                            if (isset($_template)) {
                                                $template_title = (isset($_template->templateInfo->title)) ? $_template->templateInfo->title : "";
                                                if (strtolower($variant_type) == strtolower(BoardVariant::VARIANT_PUSH_CODE)) {
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

                                                if(strtolower($variant_type) == strtolower(BoardVariant::VARIANT_INAPP_CODE)){
                                                    $background = (isset($_template->templateInfo->design->background->color)) ? $_template->templateInfo->design->background->color : "";
                                                }

                                                $action_type = (isset($_template->templateInfo->action1->type->name)) ? $_template->templateInfo->action1->type->name : "";
                                                $action_type2 = (isset($_template->templateInfo->action2->type->name)) ? $_template->templateInfo->action2->type->name : "";
                                            }

                                            // apply attribute on message
                                            $template_title = MessageFormatter::apply_attribute($row_id, NULL, $template_title);
                                            $template_content = MessageFormatter::apply_attribute($row_id, NULL, $template_content);
                                            if ($message != "") {
                                                $message = MessageFormatter::apply_attribute($row_id, NULL, $message);
                                            }
                                            //$template_title="";$template_content = "";
                                            if ($template_title == "" && $template_content == "") {
                                                $error = "Template title and content not found.";
                                                Log::error($error);
                                                $errors[] = $error;
                                                continue;
                                            }


                                            $payload = [];
                                            if(strtolower($variant_type) == strtolower(BoardVariant::VARIANT_INAPP_CODE) || strtolower($variant_type) == strtolower(BoardVariant::VARIANT_PUSH_CODE)){

                                                $isDuplicateEmail = false;
                                                // get server key
                                                $server_key = CommonHelper::getAppServerKey($app_user_app_id, $app_user_group_id, $app_user_device_type);

                                                if ($server_key == "") {
                                                    $error = 'Server Key not found. ';
                                                    Log::info($error);
                                                    $errors[] = $error;
                                                    continue;
                                                }


                                                if ($app_user_token_id == "") {
                                                    $error = "App user token id not found.";
                                                    Log::info($error);
                                                    $errors[] = $error;
                                                    continue;
                                                }

                                                if ($app_user_device_token == "") {
                                                    $error = "Device token not found.";
                                                    Log::info($error);
                                                    $errors[] = $error;
                                                    continue;
                                                }



                                                $tracking_key = RandomString::generate();
                                                $device_tokens[] = $app_user_device_token;
                                                $tracking_keys[] = $tracking_key;

                                                $_board_tracking[] = array(
                                                    'app_user_token_id' => $app_user_token_id,
                                                    'firebase_key' => $server_key,
                                                    'device_key' => "$app_user_device_token",
                                                    'device_type' => "$app_user_device_type",
                                                    'track_key' => $tracking_key
                                                );


                                                $tokens_data[] = array(
                                                    'server_key' => $server_key,
                                                    'device_type' => $app_user_device_type,
                                                    'device_token' => $device_tokens,
                                                    'tracking_key' => $tracking_keys,
                                                );


                                                /*print_r($tokens_data);
                                                echo"\n";
                                                echo"\n";
                                                echo"\n";*/

                                                $_track_key = (isset($tokens_data[0]['tracking_key'][0])) ? $tokens_data[0]['tracking_key'][0] : "";
                                                $board_inapp_view = config('engagement.url.inappviewboard') . $_track_key."?identifier=board_url";

                                                $auto_close = true;
                                                if($action_value != "" && $action_value2 != ""){
                                                    $auto_close = false;
                                                }

                                                if (strtolower($variant_type) == strtolower(BoardVariant::VARIANT_PUSH_CODE)){
                                                    $message = $template_content;
                                                }

                                                $_payload_params = [
                                                    'title' => $template_title,
                                                    'body' => $message,
                                                    'background' => $background,
                                                    "app_group_id" => $app_group_id,
                                                    'campaign_code' => $board_code,
                                                    'campaign_id' => $board_id,
                                                    'app_logo' => $app_logo,
                                                    'message_type' => $message_type,
                                                    'row_id' => $row_id,
                                                    'user_id' => $user_id,
                                                    'campaign_type' => strtolower($variant_type),
                                                    'position' => $position,
                                                    'priority' => $board_priority,
                                                    "auto_close" => $auto_close,
                                                    "action_type" => $action_type,
                                                    "action_value" => $action_value,
                                                    "language" => $userLanguageCode,
                                                    "tokens_data" => $tokens_data,
                                                    'campaign_inapp_view' => $board_inapp_view,
                                                    'campaign_start_time' => $board_start_time,
                                                    'campaign_dispatch_date' => $board_dispatch_date,
                                                    'is_board' => true,
                                                    'variant_id' => $variantId,
                                                    'variant_step_id' => $variantStepId,
                                                ];


                                                /*print_r($_payload_params);
                                                echo"\n";
                                                echo"\n";
                                                echo"\n";*/

                                                $job_name = 'PushJobWorker';
                                                // generating campaign payload for inapp and push types
                                                try {
                                                    $payload = BoardPayload::generateInAppPushPayload($_payload_params);
                                                } catch (\Exception $exception) {
                                                    $error = $exception->getMessage();
                                                    Log::error($error);
                                                    $errors[] = $error;
                                                }

                                                /*print_r($_board_tracking);*/

                                                foreach ($_board_tracking as $tracking) {
                                                    // process campaign tracking
                                                    $board_tracking = [];
                                                    $board_tracking['board_id'] = $board_id;
                                                    $board_tracking['row_id'] = $row_id;
                                                    $board_tracking['app_user_token_id'] = $tracking['app_user_token_id'];
                                                    $board_tracking['variant_step_id'] = $variantStepId;
                                                    $board_tracking['language_id'] = $userLanguageId;
                                                    $board_tracking['email'] = $to_email;
                                                    $board_tracking['firebase_key'] = $tracking['firebase_key'];
                                                    $board_tracking['device_key'] = $tracking['device_key'];
                                                    $board_tracking['device_type'] = $tracking['device_type'];
                                                    //$board_tracking['payload'] = \GuzzleHttp\json_encode($payload, true);
                                                    $board_tracking['payload'] = '';
                                                    $board_tracking['track_key'] = $tracking['track_key'];
                                                    $board_tracking['job'] = $job_name;
                                                    $board_tracking['status'] = 'added';
                                                    $board_tracking['sent'] = 0;
                                                    $board_tracking['viewed'] = 0;
                                                    //dd($campaign_tracking);
                                                    BoardTracking::create($board_tracking);

                                                    // store campaign tracking contents into cache
                                                    /*$boardUserTrackingCache = new \App\Cache\BoardUserTrackingCache();
                                                    $boardUserTrackingCache->addBoardUserTrackingCache($board_id, $row_id, $variantId, $variantStepId);*/
                                                }

                                            }
                                            elseif(strtolower($variant_type) == strtolower(BoardVariant::VARIANT_EMAIL_CODE)){

                                                $isDuplicateEmail = false;

                                                /* echo $to_email;
                                                 echo "\n";*/
                                                /*$match = [];
                                                preg_match_all('/<a\s+(?:[^>]*?\s+)?href=(["\'])(.*?)\1/', $template_content, $match);
                                                if (isset($match[2])) {
                                                    foreach ($match[2] as $url) {
                                                        $url = htmlspecialchars_decode($url);
                                                        $template_content = str_replace($url,
                                                            url('') . '/board/trackLink?enc=' . base64_encode(strtolower(BoardVariant::VARIANT_EMAIL_CODE) . '/' . $board_id . "/" . $row_id .'/' . $app_user_device_type .'/'. $url),
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
                                                                $anchorTag->setAttribute('href', url('') . '/board/trackLink?enc=' . base64_encode(strtolower(BoardVariant::VARIANT_EMAIL_CODE) . '/' . $board_id . "/" . $row_id . '/' . $app_user_device_type .'/'. $attribute->nodeValue));
                                                            }
                                                        }
                                                    }
                                                    $template_content = $dom->saveHTML();
                                                }

                                                // logic to build all track keys
                                                $tracking_key = RandomString::generate();

                                                // create tracking URL.
                                                $tracking_url = config('engagement.url.authboard') . $tracking_key;
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
                                                /*echo $template_content;
                                                echo "\n";
                                                echo "\n";
                                                echo "\n";*/

                                                // if email not present in $email_array, then we will proceed and prepare payload and save tracking
                                                if(!in_array($to_email, $email_array)){
                                                    $email_array[] = $to_email;

                                                    // generate in-app view link
                                                    $board_inapp_view = config('engagement.url.inappviewboard') . $tracking_key."?identifier=board_url";

                                                    $_payload_params = [
                                                        "app_group_id" => $app_group_id,
                                                        'campaign_code' => $board_code,
                                                        'campaign_id' => $board_id,
                                                        'tracking_key' => $tracking_key,
                                                        'row_id' => $row_id,
                                                        'user_id' => $user_id,
                                                        'campaign_type' => strtolower($variant_type),
                                                        'campaign_inapp_view' => $board_inapp_view,
                                                        'template_content' => $template_content,
                                                        "language" => $userLanguageCode,
                                                        'subject' => $variant_subject,
                                                        'from_email' => $variant_from_email,
                                                        'from_name' => $variant_from_name,
                                                        'to_email' => $to_email,
                                                        'priority' => $board_priority,
                                                        'campaign_start_time' => $board_start_time,
                                                        'is_board' => true,
                                                        'variant_id' => $variantId,
                                                        'variant_step_id' => $variantStepId
                                                    ];

                                                    $job_name = 'EmailJobWorker';

                                                    // generating campaign payload for email type
                                                    try {
                                                        $payload = BoardPayload::generateEmailPayload($_payload_params);
                                                    } catch (\Exception $exception) {
                                                        $error = $exception->getMessage();
                                                        Log::error($error);
                                                        $errors[] = $error;
                                                    }
                                                    //print_r($payload);

                                                    // save tracking into db table
                                                    $board_tracking = [];
                                                    $board_tracking['board_id'] = $board_id;
                                                    $board_tracking['row_id'] = $row_id;
                                                    $board_tracking['app_user_token_id'] = $app_user_token_id;
                                                    $board_tracking['variant_step_id'] = $variantStepId;
                                                    $board_tracking['language_id'] = $userLanguageId;
                                                    $board_tracking['email'] = $to_email;
                                                    $board_tracking['firebase_key'] = '';
                                                    $board_tracking['device_key'] = '';
                                                    $board_tracking['device_type'] = $app_user_device_type;
                                                    //$board_tracking['payload'] = \GuzzleHttp\json_encode($payload, true);
                                                    $board_tracking['payload'] = '';
                                                    $board_tracking['track_key'] = $tracking_key;
                                                    $board_tracking['job'] = $job_name;
                                                    $board_tracking['status'] = 'added';
                                                    $board_tracking['sent'] = 0;
                                                    $board_tracking['viewed'] = 0;
                                                    //dd($campaign_tracking);
                                                    BoardTracking::create($board_tracking);
                                                }
                                                else{
                                                    $isDuplicateEmail = true;
                                                }
                                            }

                                            // to avoid duplicate email case, we need to check that if $payload empty then not push in $payload_params array
                                            if(!empty($payload)){
                                                $payload_params[] = ['payload' => $payload, 'variant_code' => 'variant'.$variant_code,'priority' => $board_priority, 'payload_interval' => array('payload_interval' => $board_start_time)];
                                            }

                                        } // end of if($_variant)

                                    }
                                    /*else{
                                        $error = "Delivery control applied.";
                                        Log::info($error);
                                        $errors[] = $error;
                                    }*/

                                } // end of if isset($variantId) && isset($variantStepId)
                            } // end of foreach $user_tokens_data
                        } // end of if count($user_tokens_data) > 0

                        Log::info('here10');

                        // add board User Tracking, if isset $variantId and $variantStepId
                        if(isset($variantId) && isset($variantStepId) && $delivery_control === true){

                            Log::info('here11 - add user tracking cache');
                            $boardUserTrackingCache = new \App\Cache\BoardUserTrackingCache();
                            $boardUserTrackingCache->addBoardUserTrackingCache($board_id, $row_id, $variantId, $variantStepId, $variantStepIndex);
                        }

                        Log::info('here12');


                    } // end of foreach $variant_users_data['row_ids']

                    $variant_code++;
                } // end of if isset($variant_users_data['row_ids'])
            } // end of foreach $variants_tokens

            // payload processing
            //print_r($payload_params);

            /*// skip duplicate emails only for email type campaigns
            if ($campaign_type == Campaign::CAMPAIGN_EMAIL_CODE) {
                // skip duplicate emails from data payload
                $payload_params = self::skip_duplicates($payload_params, ["payload", "data", "to_email"]);
            }*/

            /*echo 'paylaod';
            print_r($payload_params);*/

            if(!empty($errors)){
                $response = [
                    'status' => AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                    'error' => 'error',
                    'data' => $errors
                ];
            }
            elseif(!empty($payload_params)){
                // rate limit applies only when campaign delivery type schedule
                if ($board_delivery_type ==  Board::DELIVERY_TYPE_SCHEDULE) {
                    $board_rate_limit = \App\BoardRateLimitRules::where('board_id', '=', $board_id)->select('rate_limit', 'duration_value', 'duration_unit')->first();
                    $rate_limit = (isset($board_rate_limit->rate_limit)) ? $board_rate_limit->rate_limit : "1";
                    $duration_value = (isset($board_rate_limit->duration_value)) ? $board_rate_limit->duration_value : "1000";
                    $duration_unit = (isset($board_rate_limit->duration_unit)) ? $board_rate_limit->duration_unit : "minutes";

                    $_payload = RateLimitingComponents::rateLimitingRules($payload_params, $rate_limit, $duration_value, $duration_unit);
                    /*echo 'paylaod2--';
                    print_r($_payload);*/
                    for ($val = 0; $val < count($_payload); $val++) {
                        $payload_interval = (isset($_payload[$val]['payload']['data']['interval'])) ? $_payload[$val]['payload']['data']['interval'] : $_payload[$val]['payload']['data']['start_date'];
                        $_payload[$val]['payload_interval'] = $payload_interval;

                        $priority = (isset($_payload[$val]['priority'])) ? $_payload[$val]['priority'] : Board::PRIORITY_MEDIUM;
                        $variant_name = "";
                        $variant_code = (isset($_payload[$val]['variant_code'])) ? $_payload[$val]['variant_code'] : 'variant1';
                        if($variant_code != "variant1"){
                            $variant_name = $variant_code;
                        }

                        //$delay = Carbon::now();
                        $delay = Carbon::parse($payload_interval);

                        if (strtolower($_payload[$val]['payload']['data']['type']) == strtolower(BoardVariant::VARIANT_INAPP_CODE)  || strtolower($_payload[$val]['payload']['data']['type']) == strtolower(BoardVariant::VARIANT_PUSH_CODE)) {
                            // adding PushJobWorker Queue
                            $queueName = strtolower($_payload[$val]['payload']['data']['type']).$priority.$variant_name;
                            PushJobWorker::dispatch($_payload[$val]['payload'])
                                ->onQueue("$queueName")
                                ->delay($delay);
                        } else {
                            // adding EmailJobWorker Queue
                            $queueName = strtolower($_payload[$val]['payload']['data']['type']).$priority.$variant_name;
                            EmailJobWorker::dispatch($_payload[$val]['payload'])
                                ->onQueue("$queueName")
                                ->delay($delay);
                        }

                    }

                    $payload_params = $_payload;
                }

                $response = [
                    'status' => AppStatusCodes::HTTP_OK,
                    'success' => 'success',
                    'data' => $payload_params
                ];
            }
            else{
                // payload empty case, we need to update status=complete in board_queue table
                $response = [
                    'status' => AppStatusCodes::HTTP_OK,
                    'success' => 'success',
                    'data' => $payload_params
                ];
            }

        } // end of if count($variants_tokens) > 0

        // clean payload and variant tokens memory
        unset($email_array);
        unset($payload_params);
        unset($variants_tokens);

        // return response
        return \GuzzleHttp\json_encode($response, true);

    } // end of function

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