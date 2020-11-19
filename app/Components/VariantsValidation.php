<?php

namespace App\Components;

use App\AppUsers;
use App\AppUserTokens;
use App\Cache\AppUserLoginSignupCache;
use App\Campaign;
use App\CampaignVariant;
use Illuminate\Support\Facades\Log;

class VariantsValidation
{
    /**
     * divide all provided users for a campaign
     * into variant based chunks
     *
     * @param @array $variants
     * @param @int $campaign_id
     * @param @string $campaign_type
     *
     * @return array
     * */
    public static function process($variants, $app_group_id, $campaign_type)
    {
        try {
            $_app_user_tokens = [];
            foreach ($variants as $key => $_variant) {

                $user_tokens = [];
                if (isset($_variant['row_ids'])) {

                    $limit = config('engagement.api.limit.tokens_limit'); // 1000

                    $chunksVariantRowids = array_chunk($_variant['row_ids'], $limit);

                    for ($index = 0; $index < count($chunksVariantRowids); $index++) {

                        foreach ($chunksVariantRowids[$index] as $_row_id) {

                            //get app user from cache
                            $_user = new AppUserLoginSignupCache();
//                            $_row_id = (!empty($chunksVariantRowids[$index])) ? $chunksVariantRowids[$index] : '';

                            $app_user_cache = $_user->getUserTokensFromCache($app_group_id, $_row_id);
                            $app_user_cache = json_decode($app_user_cache);

                            if (!empty($app_user_cache)) {
                                foreach ($app_user_cache as $row) {
                                    $email_notification = (isset($row->email_notification)) ? $row->email_notification : "1";
                                    $enable_notification = (isset($row->enable_notification)) ? $row->enable_notification : "1";
                                    $app_user_tokens = (isset($row->apps_users_tokens)) ? $row->apps_users_tokens : "";
                                    $revoked = (isset($app_user_tokens->revoked)) ? $app_user_tokens->revoked : "0";
                                    $device_token = (isset($app_user_tokens->device_token)) ? $app_user_tokens->device_token : "";

                                    if ($campaign_type == Campaign::CAMPAIGN_EMAIL_CODE && $email_notification == '1') {
                                        if ($revoked == 0 && $device_token != "") {
                                            $row = self::unsetExtraAttribute($row);
                                            $user_tokens[$_row_id][] = $row;
                                        }
                                    } elseif (($campaign_type == Campaign::CAMPAIGN_INAPP_CODE || $campaign_type == Campaign::CAMPAIGN_PUSH_CODE)
                                        && ($enable_notification == '1')) {

                                        if ($revoked == 0 && $device_token != "") {
                                            $row = self::unsetExtraAttribute($row);
                                            $user_tokens[$_row_id][] = $row; // $app_user_tokens
                                        }
                                    }
                                }
                            }

                        }
                    }

                    $_variant['row_ids'] = $user_tokens;

                   /* foreach ($_variant['row_ids'] as $_row_id) {

                        //get app user from cache
                        $_user = new AppUserLoginSignupCache();
                        $app_user_cache = $_user->getUserTokensFromCache($app_group_id, $_row_id);
                        $app_user_cache = json_decode($app_user_cache);

                        if (!empty($app_user_cache)) {
                            foreach ($app_user_cache as $row) {
                                $email_notification = (isset($row->email_notification)) ? $row->email_notification : "1";
                                $enable_notification = (isset($row->enable_notification)) ? $row->enable_notification : "1";
                                $app_user_tokens = (isset($row->apps_users_tokens)) ? $row->apps_users_tokens : "";
                                $revoked = (isset($app_user_tokens->revoked)) ? $app_user_tokens->revoked : "0";
                                $device_token = (isset($app_user_tokens->device_token)) ? $app_user_tokens->device_token : "";

                                if ($campaign_type == Campaign::CAMPAIGN_EMAIL_CODE && $email_notification == '1') {
                                    if ($revoked == 0 && $device_token != "") {
                                        $row = self::unsetExtraAttribute($row);
                                        $user_tokens[$_row_id][] = $row;
                                    }
                                } elseif (($campaign_type == Campaign::CAMPAIGN_INAPP_CODE || $campaign_type == Campaign::CAMPAIGN_PUSH_CODE)
                                    && ($enable_notification == '1')) {

                                    if ($revoked == 0 && $device_token != "") {
                                        $row = self::unsetExtraAttribute($row);
                                        $user_tokens[$_row_id][] = $row; // $app_user_tokens
                                    }
                                }
                            }
                        }
                    }
                    $_variant['row_ids'] = $user_tokens; */
                }
                // clean user tokens memory
                unset($user_tokens);

                // assigning variant to user tokens
                $_app_user_tokens[] = $_variant;

                // clean variant memory
                unset($_variant);
            }

            return $_app_user_tokens;
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }
    }

    /**
     * unset extra attributes from user object
     *
     * @param array $row
     *
     * @return array $row
     */
    public static function unsetExtraAttribute($row)
    {
        unset($row->company_id);
        //unset($row->app_id);
        unset($row->username);
        unset($row->firstname);
        unset($row->lastname);
        unset($row->image_url);
        unset($row->timezone);
        unset($row->latitude);
        unset($row->longitude);
        unset($row->country);
        unset($row->last_login);
        unset($row->enable_notification);
        unset($row->email_notification);
        unset($row->enabled);
        unset($row->is_deleted);
        unset($row->apps_users_tokens->row_id);
        unset($row->apps_users_tokens->user_id);
        unset($row->apps_users_tokens->app_name);
        unset($row->apps_users_tokens->app_version);
        unset($row->apps_users_tokens->app_build);
        unset($row->apps_users_tokens->instance_id);
        unset($row->apps_users_tokens->user_token);
        unset($row->apps_users_tokens->logged_in);
        unset($row->apps_users_tokens->revoked);

        return $row;
    }
}