<?php
namespace App\Cache;

use App\Cache\CacheKeys;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PopularAppsCache{

    /**
     * generate and save popular apps from cache
     *
     * @param @int app_group_id
     *
     * @return object
     */
    public static function generatePopularAppsCache($app_group_id){

        try {

            // load cache key
            $_key = new CacheKeys($app_group_id);
            //dd($app_group_id);
            $cache_key = $_key->generatePopularAppsCacheKey();

            // get cached campaign tracking
            $_stats = \Cache::get($cache_key);

            // building campaign stats
            $popular_apps = DB::table('campaign_tracking')
                ->join('app_user_token', 'campaign_tracking.app_user_token_id', '=', 'app_user_token.id')
                ->join('app', 'app.app_id', '=', 'app_user_token.app_id')
                ->where('campaign_tracking.status', '=', 'completed')
                ->where('app.app_group_id', '=', $app_group_id)
                ->groupBy('app.app_id', 'app.name', 'app.logo', 'app.app_group_id', 'app.id')
                ->orderBy('total_send', 'desc')
                ->limit(5)
                ->select(DB::raw('count(*) as total_send'), 'app.app_id','app.name','app.logo','app.app_group_id','app.id')
                ->get();

            // updating campaign stats into cache
            if (isset($popular_apps)) {

                //self::removeEntry($cache_key);
                \Cache::forever($cache_key, \GuzzleHttp\json_encode($popular_apps));

                return \GuzzleHttp\json_encode($popular_apps);
            }
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return $exception->getMessage();

        }
        return false;
    }

    /**
     * get popular apps from cache
     *
     * @param @int $app_group_id
     *
     * @return object
     */
    public static function getPopularAppsFromCache($app_group_id){

        // load cache key
        $_key = new CacheKeys($app_group_id);
        $cache_key = $_key->generatePopularAppsCacheKey();

        // get cached campaign tracking
        $popular_apps = \Cache::get($cache_key);

        return $popular_apps;
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