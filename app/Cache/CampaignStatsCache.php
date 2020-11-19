<?php
namespace App\Cache;

use App\Cache\CacheKeys;
use Illuminate\Support\Facades\DB;

class CampaignStatsCache{

    /**
     * add campaign and campaign tracking contents into
     * campaign stats cache
     *
     * @param @int app_group_id
     *
     * @return object
     */
    public static function generateCampaignStatsCache($app_group_id){

        try {

            // load cache key
            $_key = new CacheKeys($app_group_id);
            $cache_key = $_key->generateCampaignStatsKey();

            // get cached campaign tracking
            $_stats = \Cache::get($cache_key);

            // building campaign stats
            $campaign_stats = DB::table('campaign')
                                    ->join('campaign_tracking', 'campaign.id', '=', 'campaign_tracking.campaign_id')
                                    //->where('campaign.status', '=', 'active')
                                    ->where('campaign.app_group_id', '=', $app_group_id)
                                    ->select('campaign.app_group_id', 'campaign.campaign_type', 'campaign_tracking.*')
                                    ->get();

            // updating campaign stats into cache
            if (isset($campaign_stats)) {

                //self::removeEntry($cache_key);
                \Cache::forever($cache_key, \GuzzleHttp\json_encode($campaign_stats));

                return \GuzzleHttp\json_encode($campaign_stats);
            }
        } catch (\Exception $exception) {
            return $exception->getMessage();
        }
        return false;
    }

    /**
     * get campaign stats from cache
     *
     * @param @int app_group_id
     *
     * @return object
     */
    public static function getCampaignStatsFromCache($app_group_id){

        // load cache key
        $_key = new CacheKeys($app_group_id);
        $cache_key = $_key->generateCampaignStatsKey();

        // get cached campaign tracking
        $campaign_stats = \Cache::get($cache_key);

        return $campaign_stats;
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