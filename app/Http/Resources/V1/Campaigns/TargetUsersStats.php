<?php


namespace App\Http\Resources\V1\Campaigns;

use App\Apps;
use App\AppUsers;
use App\Cache\CacheKeys;
use App\Cache\CampaignSegmentCache;
use App\Campaign;
use App\CampaignRateLimitRules;
use App\CampaignVariant;
use App\Concerns\exportUsers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class TargetUsersStats
{
    use exportUsers;

    public function getStats($rowIds)
    {
        $appGroupId = Auth::user()->currentAppGroup()->id;
        $companyId = Auth::user()->id;

        $appsWithNullFirebaseKey = Apps::select(['id', 'app_id', 'platform'])
            ->where('app_group_id', $appGroupId)
            ->where('firebase_api_key', null)
            ->where('is_active', 1)
            ->get();

        $targetUsersStats = [];
        $targetUsersStats['revoked'] = 0;
        $targetUsersStats['disabled_notifications'] = 0;
        $targetUsersStats['not_login_users'] = 0;
        $targetUsersStats['unsubscribed_emails'] = 0;
        $targetUsersStats['null_emails'] = 0;
        $targetUsersStats['null_device_token'] = 0;
        $targetUsersStats['null_firebase_key'] = 0;

//        $chunkSize = config('engagement.api.export.chunk_size');
//        $rowIdsChunks = array_chunk($rowIds, $chunkSize);
//
//        $_key = new CacheKeys($appGroupId);
//
//        foreach ($rowIdsChunks as $rowIdsChunk) {
//            foreach ($rowIdsChunk as $rowID) {
//                $cache_key = $_key->generateAppUserLoginSignupKey($rowID);
//                $rowIDCacheData = \Cache::get($cache_key);
//
//                $rowIDCacheData = !empty($rowIDCacheData) && $rowIDCacheData != '[]' ? \GuzzleHttp\json_decode($rowIDCacheData)[0] : [];
//
//                if (!empty($rowIDCacheData)) {
//
//                    if ($rowIDCacheData->apps_users_tokens->revoked === 1) {
//                        $targetUsersStats['revoked']++;
//                    }
//
//                    if ($rowIDCacheData->enable_notification === 0) {
//                        $targetUsersStats['disabled_notifications']++;
//                    }
//
//                    if ($rowIDCacheData->apps_users_tokens->logged_in === 0) {
//
//                        $targetUsersStats['not_login_users']++;
//                    }
//
//                    if ($rowIDCacheData->email_notification === 0) {
//                        $targetUsersStats['unsubscribed_emails']++;
//                    }
//
//                    if (empty($rowIDCacheData->email)) {
//                        $targetUsersStats['null_emails']++;
//                    }
//
//                    if (empty($rowIDCacheData->apps_users_tokens->device_token)) {
//                        $targetUsersStats['null_device_token']++;
//                    }
//
//                    foreach ($appsWithNullFirebaseKey as $app) {
//                        if ($app->app_id == $rowIDCacheData->app_id && $rowIDCacheData->company_id == $companyId && $app->platform == $rowIDCacheData->apps_users_tokens->device_type) {
//                            $targetUsersStats['null_firebase_key']++;
//                        }
//                    }
//                }
//            }
//        }
        return $targetUsersStats;
    }
}