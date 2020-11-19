<?php
namespace App\Cache;

use App\Cache\CacheKeys;
use Illuminate\Support\Facades\DB;

class NewsfeedStatsCache{

    /**
     * build newsfeed views stats into cache
     *
     * @param @int $app_group_id
     *
     * @return object
     */
    public static function generateNewsfeedViewsStatsCache($app_group_id){

        try {

            // load cache key
            $_key = new CacheKeys($app_group_id);
            $cache_key = $_key->generateNewsfeedViewsStatsKey();

            // get cached campaign tracking
            $_stats = \Cache::get($cache_key);

            //$app_group_id = \Request::user()->currentAppGroup()->id;

            // building campaign stats
            $news_feed = DB::table('news_feed')
                                    ->join('news_feed_impression', 'news_feed.id', '=', 'news_feed_impression.news_feed_id')
                                    ->where('news_feed.status', '=', 'active')
                                    ->where('news_feed.app_group_id', '=', $app_group_id )
                                    ->where('news_feed.deleted_at', '=', NULL )
                                    ->where('news_feed_impression.deleted_at', '=', NULL )
                                    ->select('news_feed.app_group_id', 'news_feed.news_feed_template_id', 'news_feed.name', 'news_feed_impression.*')
                                    ->get();

            // updating campaign conversion into cache
            if (isset($news_feed)) {

                //self::removeEntry($cache_key);
                \Cache::forever($cache_key, \GuzzleHttp\json_encode($news_feed));

                return \GuzzleHttp\json_encode($news_feed);
            }
        } catch (\Exception $exception) {
            return $exception->getMessage();
        }
        return false;
    }

    /**
     * get newsfeed views stats from cache
     *
     * @param @int $app_group_id
     *
     * @return object
     */
    public static function getNewsfeedViewsStatsFromCache($app_group_id){

        // load cache key
        $_key = new CacheKeys($app_group_id);
        $cache_key = $_key->generateNewsfeedViewsStatsKey();

        // get cached campaign tracking
        $news_feed_views = \Cache::get($cache_key);

        return $news_feed_views;
    }

    /**
     * build newsfeed clicks stats into cache
     *
     * @param @int $app_group_id
     *
     * @return object
     */
    public static function generateNewsfeedClicksStatsCache($app_group_id){

        try {

            // load cache key
            $_key = new CacheKeys($app_group_id);
            $cache_key = $_key->generateNewsfeedClicksStatsKey();

            // get cached campaign tracking
            $_stats = \Cache::get($cache_key);

            //$app_group_id = \Request::user()->currentAppGroup()->id;

            // building campaign stats
            $news_feed = DB::table('news_feed')
                ->join('link_tracking', 'news_feed.id', '=', 'link_tracking.rec_id')
                ->where('link_tracking.rec_type', '=', 'newsfeed' )
                ->where('news_feed.status', '=', 'active')
                ->where('news_feed.app_group_id', '=', $app_group_id )
                ->where('news_feed.deleted_at', '=', NULL )
                ->select('news_feed.app_group_id', 'news_feed.news_feed_template_id', 'news_feed.name', 'link_tracking.*')
                ->get();

            // updating campaign conversion into cache
            if (isset($news_feed)) {

                self::removeEntry($cache_key);
                \Cache::forever($cache_key, \GuzzleHttp\json_encode($news_feed));

                return \GuzzleHttp\json_encode($news_feed);
            }
        } catch (\Exception $exception) {
            return $exception->getMessage();
        }
        return false;
    }

    /**
     * get newsfeed clicks stats from cache
     *
     * @param @int $app_group_id
     *
     * @return object
     */
    public static function getNewsfeedClicksStatsFromCache($app_group_id){

        // load cache key
        $_key = new CacheKeys($app_group_id);
        $cache_key = $_key->generateNewsfeedClicksStatsKey();

        // get cached campaign tracking
        $news_feed_clicks = \Cache::get($cache_key);

        return $news_feed_clicks;
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