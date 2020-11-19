<?php
namespace App\Cache;

use App\Cache\CacheKeys;
use Illuminate\Support\Facades\DB;

class CampaignConversionStatsCache{

    /**
     * generate campaign conversion stats cache
     *
     * @param @int $app_group_id
     *
     * @return object
     */
    public static function generateCampaignConversionStatsCache($app_group_id){

        try {

            // load cache key
            $_key = new CacheKeys($app_group_id);
            $cache_key = $_key->generateCampaignConversionStatsKey();

            // get cached campaign tracking
            $_stats = \Cache::get($cache_key);

            //$app_group_id = \Request::user()->currentAppGroup()->id;

            // building campaign stats
            $conversion = DB::table('campaign')
                                    ->join('app_user_activity', 'campaign.id', '=', 'app_user_activity.campaign_id')
                                    ->where('campaign.status', '=', 'active')
                                    ->where('app_user_activity.rec_type', '=', 'conversion')
                                    ->where('campaign.app_group_id', '=', $app_group_id )
                                    ->select('campaign.app_group_id', 'campaign.campaign_type', 'app_user_activity.*')
                                    ->get();

            // updating campaign conversion into cache
            if (isset($conversion)) {

                //self::removeEntry($cache_key);
                \Cache::forever($cache_key, \GuzzleHttp\json_encode($conversion));

                return \GuzzleHttp\json_encode($conversion);
            }
        } catch (\Exception $exception) {
            return $exception->getMessage();
        }
        return false;
    }

    /**
     * get campaign conversion stats from cache
     *
     * @param @int $app_group_id
     *
     * @return object
     */
    public static function getCampaignConversionStatsFromCache($app_group_id){

        // load cache key
        $_key = new CacheKeys($app_group_id);
        $cache_key = $_key->generateCampaignConversionStatsKey();

        // get cached campaign tracking
        $conversion = \Cache::get($cache_key);

        return $conversion;
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

        \Cache::forget($cache_key);
    }

}