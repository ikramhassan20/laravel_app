<?php

namespace App\Http\Resources\V1;

use App\AppUsers;
use App\Apps;
use App\AppGroup;
use App\AppUserTokens;
use App\Components\ParseResponse;
use App\Components\RenderPaginatedResponse;
use App\Components\AppStatusCodes;
use App\Components\AppStatusMessages;
use App\Helpers\CommonHelper;
use App\Http\Resources\Contracts\ProcessResourceDataContract;
use App\Http\Resources\Contracts\ResourcesContract;
use App\Http\Resources\ResourcesSteps;
use App\Cache\AppUserLoginSignupCache;
use Illuminate\Http\Request;

class AppUsersResource implements ResourcesContract, ProcessResourceDataContract
{
    use ParseResponse, ResourcesSteps, RenderPaginatedResponse;

    /**
     * Get list of campaigns.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function all(Request $request)
    {
        //
    }

    /**
     * Create a new app group.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function create(Request $request)
    {
        //
    }

    public function saveApp(Request $request)
    {
        //
    }

    public function editApp($id)
    {
        //
    }

    public function appUpdate(Request $request, \Illuminate\Database\Eloquent\Model $model)
    {
        //
    }

    public function saveAppProcess(Request $request, \Illuminate\Database\Eloquent\Model $model)
    {
        //
    }

    /**
     * Update data for a app group.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Database\Eloquent\Model $model
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function update(Request $request, \Illuminate\Database\Eloquent\Model $model)
    {
        //
    }

    /**
     * Process app users data.
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

    }

    public function notificationToggle(Request $request, $_params=[])
    {
        if(!empty($_params)){
            $params['company_id'] = $_params['company_id'];
            $params['app_name'] = $_params['app_name'];
            $params['app_id'] = $_params['app_id'];
            $params['device_type'] = $_params['device_type'];
            $params['user_id'] = $_params['user_id'];
            $params['email_notification'] = $_params['email_notification'];
        }
        else{
            $params['company_id'] = (isset($request->user()->id)) ? $request->user()->id : "";
            $params['app_name'] = $request->header('app-name');
            $params['app_id'] = $request->header('app-id');
            $params['device_type'] = $request->header('device-type');
            $params['user_id'] = $request->get('user_id');
        }

        if ($params['company_id'] == "") {
            return $this->addResponse(
                AppStatusCodes::HTTP_NOT_FOUND,
                'error',
                ['Company id not found.'],
                'data'
            );
        }

        try{
            $params['app_group_id'] = CommonHelper::getAppGroupId($params['app_id'], $params['app_name'], $params['device_type'], $params['company_id']);
        }
        catch(\Exception $exception){
            return $this->addResponse(
                AppStatusCodes::HTTP_NOT_FOUND,
                'error',
                [$exception->getMessage()],
                'data'
            );
        }

        $user = AppUsers::where('app_id', $params['app_id'])->where('company_id', $params['company_id'])
                                ->where('user_id', $params['user_id'])->where('app_group_id', $params['app_group_id'])
                                ->where('is_deleted', 0)->where('deleted_at', NULL)
                                ->first();
        if (empty($user)) {
            return $this->addResponse(
                AppStatusCodes::HTTP_NOT_FOUND,
                'error',
                ['User not found.'],
                'data'
            );
        }

        if ($request->has('enable_notification')) {
            $user->update([
                'enable_notification' => $request->get('enable_notification')
            ]);
            $login_cache = new AppUserLoginSignupCache();
            $login_cache->saveAppUserLoginCache($params);
        }

        if ($request->has('email_notification')) {
            $user->update([
                'email_notification' => $request->get('email_notification')
            ]);
            $login_cache = new AppUserLoginSignupCache();
            $login_cache->saveAppUserLoginCache($params);
        }

        if ($request->has('is_deleted')) {
            $user->update([
                'is_deleted' => $request->get('is_deleted')
            ]);
            $login_cache = new AppUserLoginSignupCache();
            $login_cache->saveAppUserLoginCache($params);
        }

        if (isset($params['email_notification'])) {
            $user->update([
                'email_notification' => $params['email_notification']
            ]);
            $login_cache = new AppUserLoginSignupCache();
            $login_cache->saveAppUserLoginCache($params);
        }

        return $this->addResponse(
            AppStatusCodes::HTTP_OK,
            AppStatusMessages::SUCCESS,
            $user,
            'data'
        );
    }
}
