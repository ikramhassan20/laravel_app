<?php
namespace App\Cache;

use App\Apps;
use App\AppGroup;
use App\AppUsers;
use App\AppUserTokens;
use App\AttributeData;
use App\Helpers\CommonHelper;
use App\Cache\CacheKeys;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AppUserLoginSignupCache{

    /**
     * save app user login cache
     *
     * @param array @params
     * @return bool
     */
    public function saveAppUserLoginCache($params){

        try {

            // prepare and parse required params
            $user_id = $params['user_id'];
            $app_id = $params['app_id'];
            $app_group_id = isset($params['app_group_id']) ? $params['app_group_id'] : '';

            if($app_group_id == "") {
                $app_name = $params['app_name'];

                $app_group_id = CommonHelper::getAppGroupId($app_id, $app_name, $params['device_type'], $params['company_id']);
                if($app_group_id == "")$app_group_id=1;
            }

            if(!isset($params['company_id'])){
                $app_group = AppGroup::find($app_group_id);
                $params['company_id'] = (isset($app_group->company_id)) ? $app_group->company_id : "1";
            }

            $appUser = AppUsers::where([
                ['user_id', $params['user_id']],
                //['app_id', $params['app_id']], //** to do */
                ['company_id', $params['company_id']],
                ['app_group_id', $app_group_id],
                ['is_deleted', 0],
                ['deleted_at', null]
            ])->first();

            // load cache key
            $_key = new CacheKeys($app_group_id);
            $cache_key = $_key->generateAppUserLoginSignupKey($appUser->row_id);
            //self::removeEntry($cache_key);

            // get cached app user login signup attributes and tokens
            $attributeTokens = \Cache::get($cache_key);
            $attributeTokens = $this->appUserLoginSignupTokens($appUser, $app_group_id, $params);
            if (!empty($attributeTokens)) {
                //self::removeEntry($cache_key);
                \Cache::forever($cache_key, \GuzzleHttp\json_encode($attributeTokens));
                return true;
            }
        } catch (\Exception $exception) {

        }

        return false;
    }

    /**
     * save app user signup cache
     *
     * @param array @params
     * @return bool
     */
    public function saveAppUserSignupCache($params){

        try {

            // prepare and parse required params
            $user_id = $params['user_id'];
            $app_id = $params['app_id'];

            $app_group_id = isset($params['app_group_id']) ? $params['app_group_id'] : '';

            if($app_group_id == '') {
                $app_name = $params['app_name'];

                $app_group_id = CommonHelper::getAppGroupId($app_id, $app_name, $params['device_type'], $params['company_id']);
                if($app_group_id == "")$app_group_id=1;
            }

            if(!isset($params['company_id'])){
                $app_group = AppGroup::find($app_group_id);
                $params['company_id'] = (isset($app_group->company_id)) ? $app_group->company_id : "1";
            }

            $appUser = AppUsers::where([
                ['user_id', $params['user_id']],
                //['app_id', $params['app_id']], //** to do */
                ['company_id', $params['company_id']],
                ['app_group_id', $app_group_id],
                ['is_deleted', 0],
                ['deleted_at', null]
            ])->first();

            // load cache key
            $_key = new CacheKeys($app_group_id);
            $cache_key = $_key->generateAppUserLoginSignupKey($appUser->row_id);
            //self::removeEntry($cache_key);

            // get cached app user login signup attributes and tokens
            $attributeTokens = \Cache::get($cache_key);
            $attributeTokens = $this->appUserLoginSignupTokens($appUser, $app_group_id, $params);

            if (!empty($attributeTokens)) {
                //self::removeEntry($cache_key);
                \Cache::forever($cache_key, \GuzzleHttp\json_encode($attributeTokens));

                return true;
            }
        } catch (\Exception $exception) {
            \Log::info($exception->getMessage());
        }

        return false;
    }

    /**
     * save app user logout cache
     *
     * @param array @params
     * @return bool
     */
    public function saveAppUserLogoutCache($params){

        try {

            // prepare and parse required params
            $user_id = $params['user_id'];
            $app_id = $params['app_id'];
            $app_group_id = isset($params['app_group_id']) ? $params['app_group_id'] : '';

            $app_group = AppGroup::find($app_group_id);
            $company_id = (isset($app_group->company_id)) ? $app_group->company_id : "1";
            $params['company_id'] = $company_id;

            $appUser = $params['app_user_object'];

            // load cache key
            $_key = new CacheKeys($app_group_id);
            $cache_key = $_key->generateAppUserLoginSignupKey($appUser->row_id);
            //self::removeEntry($cache_key);

            // get cached app user login signup attributes and tokens
            $attributeTokens = \Cache::get($cache_key);

            $attributeTokens = $this->appUserLoginSignupTokens($appUser, $app_group_id, $params);

            if (!empty($attributeTokens)) {
                //self::removeEntry($cache_key);
                \Cache::forever($cache_key, \GuzzleHttp\json_encode($attributeTokens));

                return true;
            }
        } catch (\Exception $exception) {

        }

        return false;
    }

    /**
     * grab app user tokens from cache
     *
     * @param int $app_group_id
     * @param int $company_id
     * @param int $user_id
     * @param int $app_id
     *
     * @return array
     */
    public function getUserTokensFromCache($app_group_id, $row_id){

        // load cache key
        $_key = new CacheKeys($app_group_id);
        $cache_key = $_key->generateAppUserLoginSignupKey($row_id);

        // get cached app user login attributes and tokens
        $attributeTokens = \Cache::get($cache_key);

        return (isset($attributeTokens)) ? $attributeTokens : null;
    }

    /**
     * grab app user tokens from cache
     *
     * @param int $app_group_id
     * @param int $company_id
     *
     * @return array
     */
    public function generateAppUserStats($app_group_id, $company_id){

        // load cache key
        $_key = new CacheKeys($app_group_id);
        $cache_key = $_key->generateAppUserStatsKey();

        // get cached app user login attributes and tokens
        $_users = \Cache::get($cache_key);

        // prepare and parse required params
        $params['company_id'] = $company_id;

        $appUsers = AppUsers::where([
            ['company_id', $company_id],
            ['app_group_id', $app_group_id],
            ['enabled', '1'],
            ['status', '1'],
            ['is_deleted', 0],
            ['deleted_at', NULL]
        ])
            ->orderBy('created_at', 'desc')
            ->get();

        $attributeTokens=[];
        foreach ($appUsers as $key=>$appUser){
            $params['company_id'] = $company_id;
            $params['app_id'] = $appUser->app_id;
            //dump($company_id, $app_group_id, $appUser->row_id);
            $_attributeTokens = $this->appUserLoginSignupTokens($appUser, $app_group_id, $params);
            if(!empty($_attributeTokens)){
                $attributeTokens[] = $_attributeTokens;
            }
        }

        if(!empty($attributeTokens)){
            //self::removeEntry($cache_key);
            \Cache::forever($cache_key, \GuzzleHttp\json_encode($attributeTokens));

            return \GuzzleHttp\json_encode($attributeTokens);
        }
        return false;
    }

    /**
     * grab app user tokens from cache
     *
     * @param int $app_group_id
     *
     * @return array
     */
    public function getAppUserStatsFromCache($app_group_id){

        // load cache key
        $_key = new CacheKeys($app_group_id);
        $cache_key = $_key->generateAppUserStatsKey();

        // get cached app user login attributes and tokens
        $appUserTokens = \Cache::get($cache_key);

        return $appUserTokens;
    }

    /**
     * grab app user tokens from cache
     *
     * @param int $app_group_id
     * @param int $company_id
     * @param int $row_id
     * @param int $app_id
     *
     * @return array
     */
    public function getAppUserTokensFromCache($app_group_id, $company_id, $row_id, $app_id){

        // load cache key
        $_key = new CacheKeys($app_id);
        $cache_key = $_key->generateAppUserLoginSignupKey($row_id);

        // get cached app user login attributes and tokens
        $attributeTokens = \Cache::get($cache_key);

        if(empty($attributeTokens)){

            // prepare and parse required params
            $params['company_id'] = $company_id;
            $params['row_id'] = $row_id;
            $params['app_id'] = $app_group_id;

            $appUser = AppUsers::where([
                ['row_id', $params['row_id']],
                //['app_id', $params['app_id']], //** to do */
                ['company_id', $params['company_id']],
                ['app_group_id', $app_group_id],
                ['is_deleted', 0],
                ['deleted_at', NULL]
            ])->first();

            $attributeTokens = $this->appUserLoginSignupTokens($appUser, $app_group_id, $params);

            if (!empty($attributeTokens)) {
                //self::removeEntry($cache_key);
                \Cache::forever($cache_key, \GuzzleHttp\json_encode($attributeTokens));

                return \GuzzleHttp\json_encode($attributeTokens);
            }
        }

        return $attributeTokens;
    }

    /**
     * prepare and parse all required columns from the schema
     *
     * @param array params
     *
     * @return array response
     */
    public function appUserLoginSignupTokens($attribute, $app_group_id, $params){

        // prepare and parse required params
        $company_id = $params['company_id'];
        $app_id = $params['app_id'];

        $row_id = (isset($attribute->row_id)) ? $attribute->row_id : "";
        $app_group_id = (isset($attribute->app_group_id)) ? $attribute->app_group_id : "";
        $user_id = (isset($attribute->user_id)) ? $attribute->user_id : "";
        $username = (isset($attribute->username)) ? $attribute->username : "";
        $firstname = (isset($attribute->firstname)) ? $attribute->firstname : "";
        $lastname = (isset($attribute->lastname)) ? $attribute->lastname : "";
        $email = (isset($attribute->email)) ? $attribute->email : "";
        $image_url = (isset($attribute->image_url)) ? $attribute->image_url : "";
        $timezone = (isset($attribute->timezone)) ? $attribute->timezone : "";
        $latitude = (isset($attribute->latitude)) ? $attribute->latitude : "";
        $longitude = (isset($attribute->longitude)) ? $attribute->longitude : "";
        //$lang = (isset($attribute->lang)) ? $attribute->lang : "";
        $country = (isset($attribute->country)) ? $attribute->country : "";
        $last_login = (isset($attribute->last_login)) ? $attribute->last_login : "";
        $enable_notification = (isset($attribute->enable_notification)) ? $attribute->enable_notification : "";
        $email_notification = (isset($attribute->email_notification)) ? $attribute->email_notification : "";
        $enabled = (isset($attribute->enabled)) ? $attribute->enabled : "0";
        $is_deleted = (isset($attribute->is_deleted)) ? $attribute->is_deleted : "0";


        $_attribute_data=[];
        $attributes = AttributeData::where('row_id', $row_id)->where('data_type', 'user')->get();

        if(!empty($attributes)){
            foreach($attributes as $attribute){
                if($attribute->code != ""){
                    $_attribute_data[$attribute->code] = $attribute->value;
                }
            }
        }

        // getting max device tokens allowed for a user
        $limit = config('engagement.api.limit.device_token_limit');

        // getting user attributes info
        if( isset($params['mode']) && ($params['mode'] == strtolower(AppUsers::USER_IMPORT) || $params['mode'] == strtolower(AppUsers::USER_IMPORT_API)) ) {
            $tokenData = AppUserTokens::where(['row_id' => $row_id, 'is_logged_in' => '0', 'is_revoked' => '0', 'deleted_at' => null])
                ->orderBy('updated_at', 'desc')->limit($limit)->get();
        }
        elseif(isset($params['mode']) && $params['mode'] == strtolower(AppUsers::USER_LOGOUT)){
            $tokenData = AppUserTokens::where(['row_id' => $row_id, 'is_logged_in' => '0', 'deleted_at' => null])
                ->orderBy('updated_at', 'desc')->limit($limit)->get();
        }
        elseif(isset($params['mode']) && $params['mode'] == strtolower(AppUsers::USER_REBUILD_CACHE)){
            $tokenData = AppUserTokens::where(['row_id' => $row_id, 'is_revoked' => '0', 'deleted_at' => null])
                ->orderBy('updated_at', 'desc')->limit($limit)->get();
        }
        elseif(isset($params['mode']) && $params['mode'] == strtolower(AppUsers::USER_REVOKED)){
            $tokenData = AppUserTokens::where(['row_id' => $row_id])
                ->orderBy('updated_at', 'desc')->limit($limit)->withTrashed()->get();
        }
        else{
            $tokenData = AppUserTokens::where(['row_id' => $row_id, 'is_revoked' => '0', 'deleted_at' => null])
                ->orderBy('updated_at', 'desc')->limit($limit)->get(); // 'is_logged_in' => '1',
        }

        if(isset($tokenData)){
            $_attributes = [];$i=0;
            foreach($tokenData as $token){

                // getting app user information
                $_attributes[$i]['row_id'] = $row_id;
                $_attributes[$i]['company_id'] = $company_id;
                $_attributes[$i]['app_group_id'] = $app_group_id;
                $_attributes[$i]['user_id'] = $user_id;
                $_attributes[$i]['app_id'] = $app_id;
                $_attributes[$i]['username'] = $username;
                $_attributes[$i]['firstname'] = $firstname;
                $_attributes[$i]['lastname'] = $lastname;
                $_attributes[$i]['email'] = $email;
                $_attributes[$i]['image_url'] = $image_url;
                $_attributes[$i]['timezone'] = $timezone;
                $_attributes[$i]['latitude'] = $latitude;
                $_attributes[$i]['longitude'] = $longitude;
                $_attributes[$i]['country'] = $country;
                $_attributes[$i]['last_login'] = $last_login;
                $_attributes[$i]['enable_notification'] = $enable_notification;
                $_attributes[$i]['email_notification'] = $email_notification;
                $_attributes[$i]['enabled'] = $enabled;
                $_attributes[$i]['is_deleted'] = $is_deleted;

                if(!empty($_attribute_data) && count($_attribute_data) > 0 ){
                    foreach($_attribute_data as $key=>$val){
                        $_attributes[$i][$key] = $val;
                    }
                }

                $_attributes[$i]['apps_users_tokens']['id'] = $token->id;
                $_attributes[$i]['apps_users_tokens']['row_id'] = $row_id;
                $_attributes[$i]['apps_users_tokens']['user_id'] = $user_id;
                $_attributes[$i]['apps_users_tokens']['app_name'] = $token->app_name;
                $_attributes[$i]['apps_users_tokens']['app_id'] = $token->app_id;
                $_attributes[$i]['apps_users_tokens']['app_version'] = $token->app_version;
                $_attributes[$i]['apps_users_tokens']['app_build'] = $token->app_build;
                $_attributes[$i]['apps_users_tokens']['instance_id'] = $token->instance_id;
                $_attributes[$i]['apps_users_tokens']['user_token'] = $token->user_token;
                $_attributes[$i]['apps_users_tokens']['device_token'] = $token->device_token;
                $_attributes[$i]['apps_users_tokens']['device_type'] = $token->device_type;

                if(isset($params['user_token']) && $params['user_token'] == $token->user_token){
                    $_attributes[$i]['apps_users_tokens']['logged_in'] = 0;
                }
                else{
                    $_attributes[$i]['apps_users_tokens']['logged_in'] = $token->is_logged_in;
                }

                $_attributes[$i]['apps_users_tokens']['revoked'] = $token->is_revoked;
                $_attributes[$i]['apps_users_tokens']['lang'] = $token->lang;
                $_attributes[$i]['apps_users_tokens']['status'] = $token->status;
                $_attributes[$i]['apps_users_tokens']['is_cache_sync'] = $token->is_cache_sync;
                $_attributes[$i]['apps_users_tokens']['deleted_at'] = $token->deleted_at;

                $i++;
            }
        }
        return $_attributes;
    }

    /**
     * Removes entry from cache.
     *
     * @param string cache_key
     */
    public static function removeEntry($cache_key)
    {
        if (!in_array(config('cache.default'), ['array', 'database', 'file', 'redis'])) {
            \Artisan::call('cache:clear', [
                '--tags' => $cache_key
            ]);
        }

        //\Redis::del($cache_key);
        \Cache::forget($cache_key);
    }

}