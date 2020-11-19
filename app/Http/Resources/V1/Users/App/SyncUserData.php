<?php

namespace App\Http\Resources\V1\Users\App;

use App\AppUserTokens;
use App\Concerns\Http\Resources\AppUserCacheItem;
use App\Http\Resources\Contracts\ProcessItemDataContract;
use App\Jobs\AddUserAttributesJob;
use App\AppUsers;
use App\Jobs\CreateAppsUserCacheJob;
use App\Jobs\SyncCompanyRowsCacheJob;
use App\User;
use Carbon\Carbon;

class SyncUserData implements ProcessItemDataContract
{
    use AppUserCacheItem;

    public function __construct($company_id)
    {
        $this->companyId = $company_id;
    }

    /**
     * Get cache prefix key for user app data.
     *
     * @param array $params
     */
    public function itemKey($params)
    {
        $app_name = trim(strtolower($params['app_name']));

        $this->itemCacheKey = "company_{$this->companyId}_{$app_name}_{$params['user_id']}";
    }

    /**
     * Create/update user attribute data.
     *
     * @param array $params
     *
     * @return array
     */
    public function save($params)
    {
        $insert = $this->getItemData();

        $insert = array_merge($insert, $params);

        $user_token = str_random(config('engagement.api.limit.user_token'));
        $insert['user_token'] = $user_token;

        if($params['mode'] == AppUsers::USER_LOGIN){
            $insert['is_logged_in'] = true;
        }

        $this->setItemData($insert);

        $insert['row_id'] = $this->addInDB($insert);
        $_app_user = AppUsers::find($insert['row_id']);
        $insert['enable_notification'] = $_app_user->enable_notification;
        $insert['email_notification'] = $_app_user->email_notification;

        return $insert;
    }

    /**
     * Create/update user attribute data in database.
     *
     * @param array $params
     *
     * @return void
     */
    public function addInDB($params)
    {

        $profileData = $params; //AppUsers::parseAttributes($params);
        $tokenData = $params; // AppUserTokens::parseAttributes($params);

        switch ($params['mode']) {
            case AppUsers::USER_REGISTER:
                $tokenData['is_logged_in'] = true;
                $tokenData['is_revoked'] = false;

                break;
            case AppUsers::USER_LOGIN:
                $tokenData['is_logged_in'] = true;
                $tokenData['is_revoked'] = false;

                break;
            case AppUsers::USER_LOGOUT:
                $tokenData['is_logged_in'] = false;
                $tokenData['is_revoked'] = true;

                break;
            case AppUsers::USER_IMPORT:

                $tokenData['is_logged_in'] = false;
                $tokenData['is_revoked'] = false;

                break;
        }
        $attribute = AppUsers::where([
            ['user_id', $params['user_id']],
            //['app_id', $params['app_id']], //** to do */
            ['company_id', $this->companyId],
            ['app_group_id', $params['app_group_id']],
            ['is_deleted', 0],
            ['deleted_at', NULL]
        ])->first();

        if (isset($attribute->row_id)) {
            if($params['mode'] != AppUsers::USER_IMPORT && $params['mode'] != AppUsers::USER_IMPORT_API ){
                $profileData['email'] = (isset($attribute->email)) ? $attribute->email : "";
            }
            $attribute->update($profileData);
            if(isset($tokenData['is_logged_in'])) {
                $attribute->update([
                    'last_login' => Carbon::now()
                ]);
            }
        } else {
            $profileData['company_id'] = $this->companyId;

            $attribute = AppUsers::create($profileData);
            $attribute = AppUsers::where('row_id', $attribute->row_id)->first();
        }
        $user_id = (int)$params['user_id'];
        $app_id = $params['app_id'];
        $instance_id = (isset($params['instance_id'])) ? $params['instance_id'] : "";

        $tokens = $attribute->tokens;
        if ($tokens->count() > 0) {
            $tokens = $tokens->filter(function ($token) use ($user_id, $app_id) { // , $instance_id
                return (($token->user_id === $user_id) && ($token->app_id === $app_id) ) ? $token : null; //  && ($token->instance_id === $instance_id)
            });
        }

        if (isset($attribute->row_id)) {

            $_user_token = AppUserTokens::where('row_id', '=', $attribute->row_id)
                //->where('device_type', '=', $tokenData['device_type'])
                //->where('device_token', '=', $tokenData['device_token'])
                ->first();
            if (isset($_user_token)) {
                $_user_token->update($tokenData);
            } else {
                $attribute->tokens()->create($tokenData);
            }
        }
        return (isset($attribute->row_id)) ? $attribute->row_id : "";
    }
}
