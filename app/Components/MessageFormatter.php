<?php

namespace App\Components;

use App\Apps;
use App\AppUserTokens;
use App\User;
use App\AppUsers;
use App\AttributeData;
use App\Concerns\FieldAttributes;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class MessageFormatter
 * @package App\Components
 * @todo apply attribtue formatting
 */
class MessageFormatter
{
    /**
     * apply user attribute formatting
     *
     * @param int $row_id
     * @param int $company_id
     * @param string $content
     *
     * @return string
     */
    public static function apply_attribute($row_id, $company_id, $content)
    {
        if($row_id != '') {

            // getting app user info
            $app_user = AppUsers::find($row_id);

            $_app_user = [];
            $_app_user['row_id'] = $app_user->row_id;
            $_app_user['app_group_id'] = $app_user->app_group_id;
            $_app_user['company_id'] = $app_user->company_id;
            $_app_user['user_id'] = $app_user->user_id;
            $_app_user['app_id'] = $app_user->app_id;
            $_app_user['username'] = $app_user->username;
            $_app_user['firstname'] = $app_user->firstname;
            $_app_user['lastname'] = $app_user->lastname;
            $_app_user['profile_url'] = $app_user->image_url;
            $_app_user['email'] = $app_user->email;
            $_app_user['timezone'] = $app_user->timezone;
            $_app_user['latitude'] = $app_user->latitude;
            $_app_user['longitude'] = $app_user->longitude;
            $_app_user['country'] = $app_user->country;
            $_app_user['lang'] = $app_user->lang;
            $_app_user['last_login'] = $app_user->last_login;
            $_app_user['enabled'] = $app_user->enabled;
            $_app_user['enable_notification'] = $app_user->enable_notification;
            $_app_user['email_notification'] = $app_user->email_notification;
            $_app_user['status'] = $app_user->email_notification;
            $_app_user['is_deleted'] = $app_user->is_deleted;

            foreach ($_app_user as $key => $value) {
                $content = str_replace('[[$' . $key . ']]', $value, $content);
            }

            // getting app user info
            $app_user_token=[];
            $app_user_token = AppUserTokens::where('row_id', $row_id)
                                                ->where('is_logged_in', 1)
                                                ->where('is_revoked', 0)
                                                ->where('status', 1)
                                                ->where('deleted_at', NULL)
                                                ->orderBy('updated_at', 'desc')
                                                ->get();
            $user_token=[];$_device_type='';
            if($app_user_token){
                $count=0;
                foreach($app_user_token as $_token){
                    if($count > 0) continue;
                    $user_token['app_name'] = $_token->app_name;
                    $user_token['app_version'] = $_token->app_version;
                    $user_token['app_build'] = $_token->app_build;
                    $user_token['instance_id'] = $_token->instance_id;
                    $user_token['device_token'] = $_token->device_token;
                    $user_token['device_type'] = $_token->device_type;
                    $user_token['lang'] = $_token->lang;
                    $user_token['is_login'] = $_token->is_logged_in;
                    $_device_type = $_token->device_type;
                    $count++;
                }

                foreach($user_token as $key => $value){
                    $content = str_replace('[[$' . $key . ']]', $value, $content);
                }
            }

            $field_attributes = FieldAttributes::segmentAttributeFields($app_user->app_group_id);

            foreach($field_attributes as $attribute){
                if($attribute->attribute_type == 'user'){

                    // getting attribute data
                    $attribute_data = AttributeData::where('row_id', '=', $row_id)
                                                    ->where('code', '=', $attribute->code)
                                                    ->first();
                    if($attribute_data){
                        $content = str_replace('[[$' . $attribute->code . ']]', $attribute_data->value, $content);
                    }
                }
            }

            $firebase_api_key='';
            if($_device_type != ""){
                $_app = Apps::where('app_group_id', $_app_user['app_group_id'])
                    ->where('is_active', 1)
                    ->where('platform', $_device_type)
                    ->first();

                if($_app){
                    $firebase_api_key = $_app['firebase_api_key'];
                }
            }
            $content = str_replace('[[$fire_base_key]]', $firebase_api_key, $content);

            $content = str_replace('[[$first_name]]', $_app_user['firstname'], $content);
            $content = str_replace('[[$last_name]]', $_app_user['lastname'], $content);

            // getting attribute data
            /*$attribute_data = AttributeData::where('company_id', '=', $company_id)
                                    ->where('row_id', '=', $row_id)
                                    ->get();
            if (isset($attribute_data)) {
                foreach ($attribute_data as $key => $value) {
                    $content = str_replace('[[$' . $key . ']]', $value, $content);
                }
            } */
        }

        return $content;
    }

    /**
     * apply tracking image
     *
     * @param int $user_id
     * @param string $content
     *
     * @return string
     */
    public static function apply_tracking_image($user_id, $content)
    {
        return $content;
    }
}
