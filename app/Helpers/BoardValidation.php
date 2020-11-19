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
use App\BoardTracking;
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

class BoardValidation
{
    use ParseResponse;

    public static function validation($boardId)
    {
        $boardCheck = \App\Board::select(['app_group_id', 'deleted_at', 'status'])
                                    ->where('id', $boardId)
                                    ->first();
        if (!$boardCheck) {
            $error = "Board Not Valid.";
            Log::error($error);
            throw new \Exception($error, 403);
        }
        if ($boardCheck->deleted_at != '') {
            $error = "Board has been removed.";
            Log::error($error);
            throw new \Exception($error, 403);
        }
        if ($boardCheck->status === "suspended") {
            $error = "Board has been suspended.";
            Log::error($error);
            throw new \Exception($error, 403);
        }

        $query = "SELECT app_group.id,app_group.company_id,users.is_active,users.* from ". env('DB_DATABASE') .".app_group 
                                    join ". env('DB_DATABASE') .".users 
                                    on app_group.company_id=users.id
                    where app_group.id=".$boardCheck->app_group_id;
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

    public function BoardTrackkeyValidator($params)
    {
        $boardTracking = BoardTracking::whereIn('track_key', $params['track_key'])
            ->where('device_key', $params['device_token'])->first();
        if (!$boardTracking) {
            throw new \Exception('Track key is in valid');
        }
        return $boardTracking;
    }

    public function insertLinkTracking($mode, $linkTrackingObj)
    {
        if ($mode == "both") {
            $this->updateLinkTrackingStatus($linkTrackingObj['board_tracking_viewed'], $linkTrackingObj);
        }
        unset($linkTrackingObj['board_tracking_viewed']);
        unset($linkTrackingObj['track_key']);
        unset($linkTrackingObj['device_token']);

        $linkTrackingObj = LinkTrackings::create($linkTrackingObj);
        return $linkTrackingObj;
    }

    public function updateLinkTrackingStatus($linkTrackingViewed, $track_key)
    {
        $viewed = $linkTrackingViewed + 1;
        $response = BoardTracking::whereIn('track_key', $track_key['track_key'])
            ->where('device_key', $track_key['device_token'])->update([
                'viewed' => $viewed,
                'viewed_at' => Carbon::now()
            ]);
        return $response;
    }
}