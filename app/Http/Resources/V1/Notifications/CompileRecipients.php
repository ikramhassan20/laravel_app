<?php

namespace App\Http\Resources\V1\Notifications;

use App\AppGroup;
use App\Apps;
use App\Cache\AppUserLoginSignupCache;
use App\Campaign;
use App\Components\AppPlatforms;
use App\Components\RandomString;
use App\Helpers\CommonHelper;

class CompileRecipients
{
    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $company;

    /**
     * @var array
     */
    protected $params;

    public function __construct($company, $params)
    {
        $this->company = $company;
        $this->params = $params;
    }

    /**
     * @throws \Exception
     *
     * @return array
     */
    public function compile()
    {
        $app_ids = [];
//        if ($this->params['type'] !== AppPlatforms::NOTIFICATION_TYPE_EMAIL) {
            $apps = $this->getActiveApps();
         //   dd($apps);
            if ($apps->count() == 0) {
                throw new \Exception("No active apps found for company");
            }
            $getApiKey = $this->getActiveApiKey($apps);
            if ($getApiKey == null) {
                throw new \Exception("No Api Server Key Found for current app group");
            }
            $app_ids = $apps->id;
            $appId = $apps->app_id;
            $api_key = $getApiKey->firebase_api_key;
            $this->params['app_id'] = $apps->app_id;
            $rowIds = $this->params['items'];
//        }else{
//
//        }
        for ($val = 0; $val < count($rowIds); $val++) {
            $resp = $this->getCacheData($rowIds[$val], $appId, (int)$app_ids, $this->company->id);
            $rows[]=$this->getDecodCacheData($resp,$api_key);
        }
        return $rows;
    }

    /**
     * Compile list of active apps for company.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getActiveApps()
    {
        $appGroup = AppGroup::leftjoin('app', 'app.app_group_id', '=', 'app_group.id')
            ->where('company_id', '=', $this->company->id)
            ->where('is_default', '=', '1')
            ->where('app.deleted_at', '=', NULL)
            ->first([
                'app_group.id', 'app.app_id']);
        return $appGroup;
    }

    protected function getActiveApiKey($app)
    {
        $apiKey = Apps::where('app_group_id', '=', $app->id)
              ->where('app_id', '=', $app->app_id)
           // ->where('platform', '=', $this->params['deviceType'])
            ->where('is_active', '=', '1')
            ->first(['app.firebase_api_key']);
        return $apiKey;
    }

    protected function getCacheData($rowId, $appId, $app_ids, $companyid)
    {
        //dd($rowId,$appId,$app_ids,$companyid);
        $app_user_cache = new AppUserLoginSignupCache();
        $result = $app_user_cache->getAppUserTokensFromCache($appId, $companyid, $rowId, $app_ids);
        return $result;
    }
//    protected function saveCacheData($items)
//    {
//        $app_user_cache = new AppUserLoginSignupCache();
//        for($val=0;$val<count($items);$val++)
//        {
//            $user_id=$items[$val];
//            $this->params['user_id']=$user_id;
//
//            //dd( $this->params);
//            $save_app_user[]= $app_user_cache->saveAppUserLoginCache($this->params);
//            // dd($save_app_user);
//
//        }
//        return $save_app_user;
//    }

    protected function getDecodCacheData($row,$apikey)
    {
        $finalResponse=array();
        $list = json_decode($row, true);
        if($this->params['type'] == 'email' && count($list) > 0){
            $token_id = $list[0];
            unset($list);
            $list[] = $token_id;
        }
        for ($val = 0; $val < count($list); $val++) {

            $platform = $this->params['platform'];
            $android_server_key = '';$ios_server_key='';$web_server_key='';
            if($platform == 'universal'){
                $android_server_key = CommonHelper::getAppServerKey($list[$val]['apps_users_tokens']['app_id'], $list[$val]['app_group_id'], AppPlatforms::PLATFORM_ANDROID);
                $ios_server_key = CommonHelper::getAppServerKey($list[$val]['apps_users_tokens']['app_id'], $list[$val]['app_group_id'], AppPlatforms::PLATFORM_IOS);
                $web_server_key = CommonHelper::getAppServerKey($list[$val]['apps_users_tokens']['app_id'], $list[$val]['app_group_id'], AppPlatforms::PLATFORM_WEB);
            }
            elseif($platform == 'android'){
                $android_server_key = CommonHelper::getAppServerKey($list[$val]['apps_users_tokens']['app_id'], $list[$val]['app_group_id'], $platform);
            }
            elseif($platform == 'ios'){
                $ios_server_key = CommonHelper::getAppServerKey($list[$val]['apps_users_tokens']['app_id'], $list[$val]['app_group_id'], $platform);
            }
            elseif($platform == 'web'){
                $web_server_key = CommonHelper::getAppServerKey($list[$val]['apps_users_tokens']['app_id'], $list[$val]['app_group_id'], $platform);
            }

            $finalResponse[$list[$val]['row_id']][]=array(
                    'row_id'=>$list[$val]['row_id'],
                    'firstname'=>$list[$val]['firstname'],
                    'lastname'=>$list[$val]['lastname'],
                    'email'=>$list[$val]['email'],
                    'username'=>$list[$val]['username'],
                    'user_id'=>$list[$val]['user_id'],
                    'last_login'=>$list[$val]['last_login'],
                    'app_name'=>$list[$val]['apps_users_tokens']['app_name'],
                    'app_id'=>$list[$val]['apps_users_tokens']['app_id'],
                    'app_version'=>$list[$val]['apps_users_tokens']['app_version'],
                    'app_build'=>$list[$val]['apps_users_tokens']['app_build'],
                    'device_token'=>$list[$val]['apps_users_tokens']['device_token'],
                    'device_type'=>$list[$val]['apps_users_tokens']['device_type'],
                    'logged_in'=>$list[$val]['apps_users_tokens']['logged_in'],
                    'revoked'=>$list[$val]['apps_users_tokens']['revoked'],
                    'ios_server_key'=>$ios_server_key,
                    'android_server_key'=>$android_server_key,
                    'web_server_key'=>$web_server_key,
                    'lang'=>$list[$val]['apps_users_tokens']['lang'],
            );
        }
        return $finalResponse;
    }

    /**
     * Compile list of valid recipients.
     *
     * @param array $users
     * @param array $app_ids
     *
     * @return array
     */
    protected function compileValidUsers($users, $app_ids)
    {

        // dd($users);
        return collect($users)->filter(function ($user) use ($app_ids) {
            $notification_column = ($this->params['type'] === AppPlatforms::NOTIFICATION_TYPE_EMAIL) ? 'email_notification' : 'enable_notification';
            $apps_check = !empty($app_ids) ? $user['app_id'] == $app_ids : true;
            // dd($apps_check);
            return ($this->params['items'] && $apps_check && ((bool)$user[$notification_column] === true) && ((bool)$user['enabled'] === true)) ? $user : null;
        })->map(function ($user) {
            return $user;
        })->toArray();
    }

    /**
     * Compile list of valid recipient devices.
     *
     * @param array $users
     * @param array $app_ids
     *
     * @return array
     */
    protected function compileValidDevices($users, $app_ids)
    {
        $tokens = collect($users)->map(function ($token) use ($users, $app_ids) {
            if ($this->params['type'] === AppPlatforms::NOTIFICATION_TYPE_INAPP) {
                $logged_in = ((bool)$token['logged_in']);
            }

            $platforms = in_array($token['device_type'], config('engagement.api.notifications.platforms'));
            if ($this->params['platform'] !== AppPlatforms::PLATFORM_UNIVERSAL) {
                $platforms = in_array($token['device_type'], [$this->params['platform']]);
            }

            if (isset($logged_in)) {
                return (($token['app_id'] == $app_ids) && ($logged_in === true) && ($platforms === true)) ? $token : null;
            } else {
                return (($token['app_id'] == $app_ids) && ($platforms === true)) ? $token : null;
            }
        });
        return $tokens;
    }

    /**
     * Add extra params to device tokens list.
     *
     * @param array $tokens
     * @param array $users
     * @param array $apps
     *
     * @return array
     */
    protected function addParamsToDevices($tokens, $users, $apps)
    {
        return collect($tokens)->map(function ($token) use ($users, $apps) {
            $app = collect($apps)->filter(function ($app) use ($token) {
                return (($token['device_type'] === $app['platform']) && ($token['app_id'] === $app['app_id'])) ? $app : null;
            })->first();

            $user = collect($users)->filter(function ($user) use ($token) {
                return ($token['row_id'] === $user['row_id']) ? $user : null;
            })->first();

            if (!empty($app)) {
                $token['api_key'] = $app['firebase_api_key'];
            }

            if ($this->params['type'] === AppPlatforms::NOTIFICATION_TYPE_PUSH) {
                if ($token['device_type'] === AppPlatforms::PLATFORM_IOS) {
                    $this->params['message_type'] = AppPlatforms::NOTIFICATION_TYPE_PUSH;
                }

                $notification_params = collect(config('engagement.api.notifications.params.push'))->map(function ($item, $k) use ($user, $token) {
                    if (isset($this->params[$k])) {
                        $itemValue = $this->params[$k];
                        foreach ($token as $k => $v) {
                            $itemValue = str_replace('[[$' . $k . ']]', $v, $itemValue);
                        }

                        return [$item => $itemValue];
                    }
                })->flatMap(function ($value) {
                    return $value;
                });

                if ($notification_params->isNotEmpty()) {
                    $token['notification'] = $notification_params->toArray();
                }
            } elseif ($this->params['type'] === AppPlatforms::NOTIFICATION_TYPE_INAPP) {
                $code = RandomString::generate();
                $inapp_params = [
                    'inapp_code' => $code,
                    'campaign_type' => AppPlatforms::NOTIFICATION_TYPE_INAPP,
                    'view_link' => config('engagement.url.auth') . 'notifications/' . $code,
                ];

                $this->params = array_merge($this->params, $inapp_params);
            }

            $data_params = collect(config('engagement.api.notifications.params.inapp'))->map(function ($k) use ($token) {
                return isset($this->params[$k]) ? [$k => $this->params[$k]] : null;
            })->flatMap(function ($value) {
                return $value;
            });

            if ($data_params->isNotEmpty()) {
                $data_params = $data_params->toArray();

                if (isset($data_params['message'])) {
                    if ($this->params['type'] === AppPlatforms::NOTIFICATION_TYPE_INAPP) {
                        $itemValue = $data_params['message'];
                        foreach ($token as $k => $v) {
                            $itemValue = str_replace('[[$' . $k . ']]', $v, $itemValue);
                        }

                        \Cache::forever("notifications_{$code}", \GuzzleHttp\json_encode([
                            'notification' => $itemValue,
                            'viewed' => false
                        ]));

                        unset($data_params['inapp_code']);
                    }
                }

                unset($data_params['message']);
                $token['data'] = $data_params;
            }

            return $token;
        })->toArray();
    }

    /**
     * Add extra params.
     *
     * @param array $users
     *
     * @return array
     */
    protected function addParamsToUsers($users)
    {
        return collect($users)->map(function ($user) {
            $itemValue = $this->params['message'];
            foreach ($user as $k => $v) {
                $itemValue = str_replace('[[$' . $k . ']]', $v, $itemValue);
            }
            $user['message'] = $itemValue;

            $user['subject'] = $this->params['title'];
            if (!empty($this->params['title'])) {
                foreach ($user as $k => $v) {
                    $user['subject'] = str_replace('[[$' . $k . ']]', $v, $user['subject']);
                }
            }

            return $user;
        })->toArray();
    }
}
