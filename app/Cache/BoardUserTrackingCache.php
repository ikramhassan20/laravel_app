<?php

namespace App\Cache;

use Log;

/**
 * Class BoardUserTrackingCache
 * @package App\Cache
 */
class BoardUserTrackingCache
{

    /**
     * @param $board_id
     * @param $row_id
     * @param $variant_id
     * @param $variant_step_id
     * @param null $last_sent_date
     * @return bool|string
     */
    public static function addBoardUserTrackingCache($board_id, $row_id, $variant_id, $variant_step_id, $variant_step_index)
    {
        try {
            // load cache key
            $_key = new CacheKeys(NULL);
            $cache_key = $_key->generateBoardUserTrackingKey($board_id, $row_id);

            // get cached board tracking
            $board_user_tracking = \Cache::get($cache_key);

            // get all board tracking
            $board_user_tracking = array(
                'board_id' => $board_id,
                'row_id' => $row_id,
                'variant_id' => $variant_id,
                'variant_step_id' => $variant_step_id,
                'variant_step_index' => $variant_step_index
            );

            if (isset($board_user_tracking)) {
                //self::removeEntry($cache_key);
                \Cache::forever($cache_key, \GuzzleHttp\json_encode($board_user_tracking));

                return \GuzzleHttp\json_encode($board_user_tracking);
            }
        }
        catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }

        return false;
    }


    /**
     * @param $board_id
     * @param $row_id
     * @param $language
     * @param $variant_step_id
     * @param $content
     * @param $last_sent_date
     * @param $sent_count
     * @param bool $isUpdateVariantId
     * @return bool|string
     */
    public static function updateBoardTrackingCache($board_id, $row_id, $variant_id, $variant_step_id, $last_sent_date)
    {
        try {
            // load cache key
            $_key = new CacheKeys(NULL);
            $cache_key = $_key->generateBoardUserTrackingKey($board_id, $row_id);

            // get cached campaign tracking
            $board_user_tracking = \Cache::get($cache_key);
            $board_user_tracking = \GuzzleHttp\json_decode($board_user_tracking, true);

            $board_user_tracking['board_id'] = $board_id;
            $board_user_tracking['row_id'] = $row_id;
            $board_user_tracking['variant_id'] = $variant_id;
            $board_user_tracking['variant_step_id'] = $variant_step_id;
            $board_user_tracking['last_sent_date'] = $last_sent_date;

            if (isset($board_user_tracking)) {
                //self::removeEntry($cache_key);
                \Cache::forever($cache_key, \GuzzleHttp\json_encode($board_user_tracking));

                return \GuzzleHttp\json_encode($board_user_tracking);
            }
        }
        catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }

        return false;
    }


    /**
     * @param $board_id
     * @param $row_id
     * @return bool|mixed
     */
    public static function getBoardUserTrackingCache($board_id, $row_id)
    {
        // load cache key
        $_key = new CacheKeys(NULL);
        $cache_key = $_key->generateBoardUserTrackingKey($board_id, $row_id);

        // get cached board tracking
        $board_user_tracking = \Cache::get($cache_key);

        if (isset($board_user_tracking)) {
            return \GuzzleHttp\json_decode($board_user_tracking);
        }

        return false;
    }


    /**
     * Removes entry from cache.
     * @param string $cache_key
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