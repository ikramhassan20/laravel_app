<?php

namespace App\Http\Controllers;

use App\Apps;
use App\AppGroup;
use App\Campaign;
use App\LinkTrackings;
use App\CampaignTracking;
use App\Notification;
use App\User;
use App\AppUsers;
use Composer\Util\Platform;
use Illuminate\Http\Request;
use App\Http\Resources\V1\AppUsersResource;
use Illuminate\Support\Facades\App;


class UnsubscribeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //return view('home');
    }

    public function unsubscribeEmail(Request $request)
    {
        // 'gVBGpYNYYIYKCrufTjdF'
        $data['track_key'] = $request->input('enc');
        $_track_key = base64_decode($data['track_key']);

        if($_track_key != ""){
            $_tracking = CampaignTracking::where('track_key', '=', $_track_key)->first();
            if(!$_tracking){
                abort(403, 'Invalid Request.', $headers=[]);
            }

            $params['device_type'] = (isset($_tracking->device_type)) ? strtolower($_tracking->device_type) : 'android';
            $payload = (isset($_tracking->payload)) ? json_decode($_tracking->payload) : '';
            $params['campaign_id'] = (isset($payload->data->campaign_id)) ? $payload->data->campaign_id : "";
            $params['row_id'] = (isset($payload->data->row_id)) ? $payload->data->row_id : "";
            $params['user_id'] = (isset($payload->data->user_id)) ? $payload->data->user_id : "";
            $params['campaign_id'] = $_tracking->campaign_id;

            $appUser = AppUsers::find($_tracking->row_id);
            if(!$appUser){
                throw new \Exception('No User found.');
            }
            $params['user_id'] = (isset($appUser->user_id)) ? $appUser->user_id: "";
            $params['row_id'] = (isset($appUser->row_id)) ? $appUser->row_id : "";

            $campaign = Campaign::find($params['campaign_id']);
            $params['app_group_id'] = (isset($campaign->app_group_id)) ? $campaign->app_group_id : "";

            $app_group = AppGroup::find($params['app_group_id']);
            $params['company_id'] = (isset($app_group->company_id)) ? $app_group->company_id : "" ;

            $appResult = Apps::where('app_group_id', $params['app_group_id'])
                ->where('platform', $params['device_type'])
                ->where('is_active', 1)
                ->where('deleted_at', NULL)
                ->first();

            if(!$appResult){
                //throw new \Exception('No App found.');
                abort(403, 'No App found.', $headers=[]);
            }

            $_company = User::find($params['company_id']);
            $data['company_name'] = (isset($_company->name)) ? $_company->name : "";

            return view('unsubscribe', compact('data'));
        }
        else{
            abort(404, 'Invalid Request.', $headers=[]);
        }
    }

    public function unsubscribedUserFromEmail(Request $request)
    {
        $data['track_key'] = $request->input('enc');
        $_track_key = base64_decode($data['track_key']);

        if($_track_key != ""){
            $_tracking = CampaignTracking::where('track_key', '=', $_track_key)->first();
            if(!$_tracking){
                abort(403, 'Invalid Request.', $headers=[]);
            }

            $params['device_type'] = (isset($_tracking->device_type)) ? strtolower($_tracking->device_type) : 'android';
            $payload = (isset($_tracking->payload)) ? json_decode($_tracking->payload) : '';
            $params['campaign_id'] = (isset($payload->data->campaign_id)) ? $payload->data->campaign_id : "";
            $params['row_id'] = (isset($payload->data->row_id)) ? $payload->data->row_id : "";
            $params['user_id'] = (isset($payload->data->user_id)) ? $payload->data->user_id : "";
            $params['campaign_id'] = $_tracking->campaign_id;

            $appUser = AppUsers::find($_tracking->row_id);
            if(!$appUser){
                throw new \Exception('No User found.');
            }
            $params['user_id'] = (isset($appUser->user_id)) ? $appUser->user_id: "";
            $params['row_id'] = (isset($appUser->row_id)) ? $appUser->row_id : "";

            $campaign = Campaign::find($params['campaign_id']);
            $params['app_group_id'] = (isset($campaign->app_group_id)) ? $campaign->app_group_id : "";

            $app_group = AppGroup::find($params['app_group_id']);
            $params['company_id'] = (isset($app_group->company_id)) ? $app_group->company_id : "" ;

            $appResult = Apps::where('app_group_id', $params['app_group_id'])
                ->where('platform', $params['device_type'])
                ->where('is_active', 1)
                ->where('deleted_at', NULL)
                ->first();

            if(!$appResult){
                throw new \Exception('No App found.');
            }

            $_company = User::find($params['company_id']);
            $data['company_name'] = (isset($_company->name)) ? $_company->name : "";

            $params['email_notification'] = 0;
            $params['app_id'] = $appResult->app_id;
            $params['app_name'] = $appResult->name;

            $_resource = new AppUsersResource();
            $response = $_resource->notificationToggle($request, $params);

            $status_code = $response->getStatusCode();
            if($status_code == 200){
                return view('unsubscribed', compact('data'));
            }
            else{
                abort(403, 'No User Found.', $headers=[]);
            }
        }
        else{
            abort(404, 'Invalid Request.', $headers=[]);
        }
    }
}
