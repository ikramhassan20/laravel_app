<?php
/**
 * Created by PhpStorm.
 * User: ets-rebel
 * Date: 2/21/19
 * Time: 3:42 PM
 */

namespace App\Helpers;

use App\AppGroup;
use App\Apps;
use App\Attribute;
use App\Campaign;
use App\CampaignAction;
use App\CampaignTracking;
use App\Components\AppPlatforms;
use App\Components\CampaignWorkerPayload;
use App\Components\ParseResponse;
use App\LinkTrackings;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CampaignValidation
{
    use ParseResponse;

    public static function validation($campaignId)
    {
        $campaigCheck = Campaign::select(['app_group_id', 'deleted_at', 'status'])
                                    ->where('id', $campaignId)
                                    ->first();
        if (!$campaigCheck) {
            $error = "Campaign Not Valid.";
            Log::error($error);
            throw new \Exception($error, 403);
        }
        if ($campaigCheck->deleted_at != '') {
            $error = "Campaign has been removed.";
            Log::error($error);
            throw new \Exception($error, 403);
        }
        if ($campaigCheck->status === "suspended") {
            $error = "Campaign has been suspended.";
            Log::error($error);
            throw new \Exception($error, 403);
        }

        $query = "SELECT app_group.id,app_group.company_id,users.is_active,users.* from ". env('DB_DATABASE') .".app_group 
                                    join ". env('DB_DATABASE') .".users 
                                    on app_group.company_id=users.id
                    where app_group.id=".$campaigCheck->app_group_id;
        $companyCheck = DB::select(DB::raw($query));
        if(empty($companyCheck)) {
            $error = "Company id is not valid.";
            Log::error($error);
            throw new \Exception($error, 403);
        }
        if(isset($companyCheck)){
            $is_active = (isset($companyCheck[0]->is_active )) ? $companyCheck[0]->is_active : false;
            if ((bool)$is_active === false) {
                $error = "Company has been deactivated";
                Log::error($error);
                throw new \Exception($error, 403);
            }
        }
        return true;
    }

    public static function campaignSendTest($receiver, $data)
    {
        $action_type = (isset($data['action']['type'])) ? $data['action']['type'] : "";
        if ($data['type'] == "push") {
            $action_value = $data['action']['value'];
        } else {
            $action_value = "";
        }
        $_params = [
            'title' => (isset($data['title'])) ? $data['title'] : "",
            'body' => (isset($receiver['message'])) ? $receiver['message'] : "",
            'icon' => "",
            'api_key' => $receiver['api_key'],
            'device_type' => $receiver['device_type'],
            'device_token' => $receiver['device_token'],
            'campaign_code' => "",
            'message_type' => (isset($data['message_type'])) ? str_replace("dialog", "dialogue", strtolower($data['message_type'])) : "dialogue",
            'track_key' => "",
            'user_id' => $receiver['user_id'],
            'link' => "",
            'campaign_type' => $data['type'], // inapp / push
            'message_position' => (isset($data['message_position'])) ? strtolower($data['message_position']) : "top",
            'message' => (isset($receiver['html_content'])) ? $receiver['html_content'] : "",
            "view_link" => "",
            'priority' => "normal",
            "is_hermis_platform" => true,
            "is_silent" => false,
            "backgroundColor" => "#FFFFFF",
            "action_type" => $action_type,
            "action_value" => $action_value,
            "sound" => "DEFAULT"
        ];

        if ($receiver['device_type'] == AppPlatforms::PLATFORM_IOS) {
            if ($data['type'] === AppPlatforms::NOTIFICATION_TYPE_INAPP) {
                $payload = CampaignWorkerPayload::generateWorkerInAppPayload($_params);
            } else {
                $payload = CampaignWorkerPayload::generateWorkerPushPayload($_params);
            }
        } elseif ($receiver['device_type'] == AppPlatforms::PLATFORM_ANDROID) {
            if ($data['type'] === AppPlatforms::NOTIFICATION_TYPE_INAPP) {
                $payload = CampaignWorkerPayload::generateAndroidWorkerInAppPayload($_params);
            } else {
                $payload = CampaignWorkerPayload::generateAndroidWorkerPushPayload($_params);
            }
        }

        return $payload;
    }

    public function getAppGroupId($userInfo)
    {
        $appGroupCheck = Apps::leftjoin('app_group', 'app_group.id', '=', 'app.app_group_id')
            ->where('app_group.company_id', '=', $userInfo['company_id'])
            ->where('app_group.deleted_at', '=', NULL)
            ->where('app.app_id', $userInfo['appId'])
            ->where('app.name', $userInfo['appName'])
            ->where('app.is_active','1')
            ->where('app.platform', $userInfo['device_type'])->first();
        if (!$appGroupCheck) {
            throw new \Exception('App group does not exits');
        }
        return $appGroupCheck;
    }

    public function campaignTrackkeyValidator($params)
    {
        $campaignTracking = CampaignTracking::whereIn('track_key', $params['track_key'])
            ->where('device_key', $params['device_token'])->first();
        if (!$campaignTracking) {
            throw new \Exception('Track key is in valid');
        }
        return $campaignTracking;
    }

    public function insertLinkTracking($mode, $linkTrackingObj)
    {
        if ($mode == "both") {
            $this->updateCampaignTrackingStatus($linkTrackingObj['campaign_tracking_viewed'], $linkTrackingObj);
        }
        unset($linkTrackingObj['campaign_tracking_viewed']);
        unset($linkTrackingObj['track_key']);
        unset($linkTrackingObj['device_token']);

        $linkTrackingObj = LinkTrackings::create($linkTrackingObj);
        return $linkTrackingObj;
    }

    public function updateCampaignTrackingStatus($campaignTrackingViewed, $track_key)
    {
        $viewed = $campaignTrackingViewed + 1;
        $response = CampaignTracking::whereIn('track_key', $track_key['track_key'])
            ->where('device_key', $track_key['device_token'])->update([
                'viewed' => $viewed,
                'viewed_at' => Carbon::now()
            ]);
        return $response;
    }

    public function attributeCollection($app_group_id, $collection){

        // building dynamic query
        $query = " Select x.campaign_id from ( SELECT t1.campaign_id, t3.code, t1.value FROM ". env('DB_DATABASE') .".campaign_action as t1 join ";
        $query .= "(select it1.campaign_id, count(*) as total_actions from ". env('DB_DATABASE') .".campaign_action as it1 join ". env('DB_DATABASE') .".campaign as it2 on it1.campaign_id = it2.id ";
        $query .= "where it1.action_type = \"trigger\" and it2.app_group_id = '". $app_group_id ."' ";
        $query .= " group by it1.campaign_id having count(*) = '". count($collection) ."'  )";
        $query .= " as t2 on t1.campaign_id = t2.campaign_id join ". env('DB_DATABASE') .".attribute as t3 on t1.action_id = t3.id ";

        // collection of attributes are provided
        if(count($collection) > 0){
           $i=1;
           foreach ($collection as $key){
               if($i==1){
                   $where = " where ";
               }
               else{
                   $where = " OR ";
               }

               if($key['value'] == ""){
                   $query .= $where ." ( t3.code = '". $key['code'] ."' AND t1.value is NULL ) " ;
               }
               else{
                   $query .= $where ." ( t3.code = '". $key['code'] ."' AND t1.value = '". $key['value'] ."' ) " ;
               }

               $i++;
           }
        }
        $query .= " ) as x group by campaign_id having count(*) = '". count($collection) ."' ";
        //dump($query);

        // executing collection query
        $attribute_collection = DB::select($query);
        $attribute_collection = json_encode($attribute_collection);
        $attribute_collection = json_decode($attribute_collection, true);

        // when no data found
        if(!$attribute_collection){
            throw new \Exception('Attribute code or collection is not valid.');
        }

        // return output response
        return $attribute_collection;
    }

    public function attributeValidator($app_group_id, $code)
    {
        $attributeCodeCheck = Attribute::where('app_group_id', $app_group_id)
                                            ->where('code', $code)
                                            ->where('attribute_type', 'action')
                                            ->first();
        if (!$attributeCodeCheck) {
            throw new \Exception('Attribute Code is not valid');
        }
        return $attributeCodeCheck;
    }

    public function CampaignActionValidator($attribute_action, $value)
    {
        $action_id = $attribute_action->id;
        $campaignActionCheck = CampaignAction::where('action_id', '=', $action_id)
                                                ->where('value', '=', $value)
                                                ->where('action_type', '=', 'trigger')
                                                ->get();
        if (count($campaignActionCheck) == 0) {
            throw new \Exception('Campaign action not valid');
        } else {
            return $campaignActionCheck;
        }

    }
}