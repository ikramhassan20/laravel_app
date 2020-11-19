<?php

namespace App\Cache;

use App\Cache\CacheKeys;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Log;

/**
 * Class OnceBoardRowIdsCache
 * @package App\Cache
 */
class OnceBoardRowIdsCache
{

    /**
     * Function used to save rowIds in cache
     * @param int $board_id
     * @param array $board_row_ids
     * @return bool
     */
    public function saveOnceBoardRowIdsCache($app_group_id, $board_id, $board_row_ids)
    {
        try {

            // get cache key
            $_key = new CacheKeys($app_group_id);
            $cache_key = $_key->generateOnceTypeBoardRowIdsCacheKey($board_id);

            $json_board_row_ids = \GuzzleHttp\json_encode($board_row_ids);

            \Cache::forever($cache_key, $json_board_row_ids);

            return $board_row_ids;

        }
        catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }

        return false;
    }


    /**
     * Function used to get rowIds from cache
     * @param int $board_id
     * @return bool|mixed
     */
    public function getOnceBoardRowIdsCache($app_group_id, $board_id)
    {

        try{
            // get cache key
            $_key = new CacheKeys($app_group_id);
            $cache_key = $_key->generateOnceTypeBoardRowIdsCacheKey($board_id);

            // get cached Once board rowids
            $json_board_row_ids = \Cache::get($cache_key);

            if (isset($json_board_row_ids)) {
                return \GuzzleHttp\json_decode($json_board_row_ids);
            }
        }
        catch(\Exception $exception){
            Log::error($exception->getMessage());
        }

        return false;
    }


    /**
     * Function used to remove cache key
     * @param String $cache_key
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