<?php

namespace App\Cache;

use App\BoardVariantStep;
use Log;

/**
 * Class BoardTrackingCache
 * @package App\Cache
 */
class BoardTrackingCache
{

    /**
     * @param $board_id
     * @param $row_id
     * @param $language
     * @param $variant_step_id
     * @param $content
     * @return bool|string
     */
    public function addBoardTrackingCache($board_id, $row_id, $language, $variant_step_id, $content)
    {
        try {
            // load cache key
            $_key = new CacheKeys(NULL);
            $cache_key = $_key->generateBoardTrackingKey($board_id, $row_id, $language, $variant_step_id);

            // get cached board tracking
            $board_tracking = \Cache::get($cache_key);

            // get all board tracking
            $board_tracking = array(
                'board_id' => $board_id,
                'row_id' => $row_id,
                'language' => $language,
                'variant_id' => BoardVariantStep::find($variant_step_id)->variant->id,
                'variant_step_id' => $variant_step_id,
                'content'  => $content
            );

            if (isset($board_tracking)) {
                //self::removeEntry($cache_key);
                \Cache::forever($cache_key, \GuzzleHttp\json_encode($board_tracking));

                return \GuzzleHttp\json_encode($board_tracking);
            }
        }
        catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }

        return false;
    }


    public function updateBoardTrackingCache($board_id, $row_id, $language, $variant_step_id, $content, $last_sent_date, $sent_count, $isUpdateVariantId = false)
    {
        try {
            // load cache key
            $_key = new CacheKeys(NULL);
            $cache_key = $_key->generateBoardTrackingKey($board_id, $row_id, $language, $variant_step_id);

            // get cached campaign tracking
            $board_tracking = \Cache::get($cache_key);

            // get all campaign tracking
            $board_tracking = array(
                'board_id' => $board_id,
                'row_id' => $row_id,
                'language' => $language,
                'variant_step_id' => $variant_step_id,
                'content'     => $content,
                'last_sent_date' => $last_sent_date,
                'sent_count' => $sent_count
            );

            if($isUpdateVariantId){
                $board_tracking['variant_id'] = BoardVariantStep::find($variant_step_id)->variant->id;
            }

            if (isset($board_tracking)) {

                //self::removeEntry($cache_key);
                \Cache::forever($cache_key, \GuzzleHttp\json_encode($board_tracking));

                return \GuzzleHttp\json_encode($board_tracking);
            }
        } catch (\Exception $exception) {

        }

        return false;
    }


    /**
     * @param $board_id
     * @param $row_id
     * @param $language
     * @param $variant_step_id
     * @return bool|mixed
     */
    public function getBoardTrackingCache($board_id, $row_id, $language, $variant_step_id)
    {
        // load cache key
        $_key = new CacheKeys(NULL);
        $cache_key = $_key->generateBoardTrackingKey($board_id, $row_id, $language, $variant_step_id);

        // get cached board tracking
        $board_tracking = \Cache::get($cache_key);

        if (isset($board_tracking)) {
            return \GuzzleHttp\json_decode($board_tracking);
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