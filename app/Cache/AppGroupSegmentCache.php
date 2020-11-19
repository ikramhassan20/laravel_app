<?php

namespace App\Cache;

use App\AttributeData;
use App\Cache\CacheKeys;
use App\AppGroup;
use App\Segment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Log;

class AppGroupSegmentCache
{

    /**
     * build and grab the cache key and save App Group segment cache
     *
     * @param \Illuminate\Database\Eloquent\Model $segment
     * @return bool
     */
    public function saveAppGroupSegmentCache($segment)
    {

        try {
            $app_group_id = $segment->app_group_id;
            $segment_id = $segment->id;

            // load cache key
            $_key = new CacheKeys($app_group_id);
            $cache_key = $_key->generateAppGroupSegmentKey();

            // get cached segments
            $segments = \Cache::get($cache_key);

            if (isset($segments)) {
                $segments = \GuzzleHttp\json_decode($segments, true);
            } else {
                $segments = [];
            }

            if (!in_array($segment_id, $segments)) {
                $segments[] = $segment->id;
                //self::removeEntry($cache_key);
                \Cache::forever($cache_key, \GuzzleHttp\json_encode($segments));
                return true;
            }
        } catch (\Exception $exception) {
            return $exception->getMessage();
        }

        return false;
    }

    /**
     * build and grab the cache key and save App Group segment rows cache
     *
     * @param \Illuminate\Database\Eloquent\Model $segment
     * @param bool sp
     *
     * @return bool
     */
    public function saveAppGroupSegmentRowsCache_OLD($segment, $sp = false)
    {

        try {

            // prepare and parse data
            $app_group_id = $segment->app_group_id;
            $segment_id = $segment->id;
            $status = (isset($segment->is_active)) ? $segment->is_active : 1;

            // Update segment rows for active segments only
            if ($status == '1') {

                // load cache key
                $_key = new CacheKeys($app_group_id);
                $cache_key = $_key->generateAppGroupSegmentRowsKey($segment_id);

                // call sp that returns all segment rows
                $rows = \DB::select("CALL sp_get_segment_rowid({$segment_id})");

                $segment_rows = [];
                if(!empty($rows) ){
                    $_rows = array_map(
                        function ($value) {
                            return $value->row_id;
                        },
                        $rows
                    );

                    $segment_rows = array_values(array_unique($_rows));
                }

                //self::removeEntry($cache_key);
                \Cache::forever($cache_key, \GuzzleHttp\json_encode($segment_rows));
                return true;
            }
            return true;
        } catch (\Exception $exception) {

            // returns error exception message
            return $exception->getMessage();
        }
        return false;
    }


    /**
     * @param $segment
     * @param bool $sp
     * @return bool|string
     */
    public function saveAppGroupSegmentRowsCache($segment, $sp = false)
    {
        try {
            // prepare and parse data
            $app_group_id = $segment->app_group_id;
            $segment_id = $segment->id;
            $status = (isset($segment->is_active)) ? $segment->is_active : 1;

            // Update segment rows for active segments only
            if ($status == '1') {

                // load cache key
                $_key = new CacheKeys($app_group_id);
                $cache_key = $_key->generateAppGroupSegmentRowsKey($segment_id);
                $cache_prefix =  config('cache.prefix');
                $cache_key_with_prefix = $cache_prefix . ':' .$cache_key;

                self::removeEntry($cache_key);

                $condition = true;
                $page = 0;
                $limit = (!empty(config('engagement.api.limit.sp_limit')) ? config('engagement.api.limit.sp_limit') : 50000) ;
                while($condition){

                    $offset = $page * $limit;
                    $rows = \DB::select("CALL sp_get_segment_rowid({$segment->id}, $offset, $limit)");
                    if(!empty($rows)){
                        $_rows = array_map(
                            function ($value) {
                                return $value->row_id;
                            },
                            $rows
                        );
                        $segment_rows = array_values(array_unique($_rows));
                        Redis::sadd($cache_key_with_prefix, $segment_rows);
                        unset($segment_rows);
                    }
                    $page++;

                    // while loop termination condition
                    if(empty($rows)){
                        $condition = false;
                    }
                }

                return true;
            }
            return true;
        } catch (\Exception $exception) {
            // returns error exception message
            return $exception->getMessage();
        }
        return false;
    }

    /**
     * get all rows from App Group segment rows cache
     *
     * @param int $app_group_id
     * @param int $segment_id
     *
     * @return array
     */
    public function getAppGroupSegmentRowsCache_OLD($app_group_id, $segment_id)
    {
        // load cache key
        $_key = new CacheKeys($app_group_id);
        $cache_key = $_key->generateAppGroupSegmentRowsKey($segment_id);

        // get cached segments
        $segment_rows = \Cache::get($cache_key);
        //self::removeEntry($cache_key);
        if (!isset($segment_rows)) {

            $rows = \DB::select("CALL sp_get_segment_rowid({$segment_id})");
            $rows = collect($rows)->filter(function ($row) {
                return isset($row->row_id) ? $row->row_id : null;
            });

            if ($rows->count() > 0) {
                $segment_rows = $rows->pluck('row_id')->unique()->toArray();

                //self::removeEntry($cache_key);
                \Cache::forever($cache_key, \GuzzleHttp\json_encode($segment_rows));

                return $segment_rows;
            }
        } else {
            $segment_rows = \GuzzleHttp\json_decode($segment_rows);
        }

        return $segment_rows;
    }

    /**
     * @param $app_group_id
     * @param $segment_id
     * @return array|mixed
     */
    public function getAppGroupSegmentRowsCache($app_group_id, $segment_id)
    {
        // load cache key
        $_key = new CacheKeys($app_group_id);
        $cache_key = $_key->generateAppGroupSegmentRowsKey($segment_id);
        $cache_prefix =  config('cache.prefix');
        $cache_key_with_prefix = $cache_prefix . ':' .$cache_key;

        $segment_rows = NULL;
        $cacheType = Redis::type($cache_key_with_prefix);
        if($cacheType == "set"){
            $segment_rows = Redis::smembers($cache_key_with_prefix);
        }
        elseif($cacheType == "string"){
            $segment_rows = \Cache::get($cache_key);
            $segment_rows = \GuzzleHttp\json_decode($segment_rows);
        }

        if (!isset($segment_rows)) {
            $condition = true;
            $page = 0;
            $limit = (!empty(config('engagement.api.limit.sp_limit')) ? config('engagement.api.limit.sp_limit') : 50000) ;
            while($condition){
                $offset = $page * $limit;
                $rows = \DB::select("CALL sp_get_segment_rowid({$segment_id}, $offset, $limit)");
                if(!empty($rows)){
                    $_rows = array_map(
                        function ($value) {
                            return $value->row_id;
                        },
                        $rows
                    );
                    $segment_rows = array_values(array_unique($_rows));
                    Redis::sadd($cache_key_with_prefix, $segment_rows);
                    unset($segment_rows);
                }
                $page++;

                // while loop termination condition
                if(empty($rows)){
                    $condition = false;
                }
            }

            $segment_rows = Redis::smembers($cache_key_with_prefix);
        }

        return $segment_rows;
    }


    public function getSegmentRowsCount($app_group_id, $segment_id)
    {
        $_key = new CacheKeys($app_group_id);
        $cache_key = $_key->generateAppGroupSegmentRowsKey($segment_id);
        $cache_prefix =  config('cache.prefix');
        $cache_key_with_prefix = $cache_prefix . ':' .$cache_key;

        $cacheType = Redis::type($cache_key_with_prefix);
        if($cacheType == 'set'){
            $segmentRowsCount = Redis::scard($cache_key_with_prefix);
            return $segmentRowsCount;
        }
        elseif($cacheType == 'string'){
            $data = \Cache::get($cache_key);
            $data = !empty($data) ? \GuzzleHttp\json_decode($data, true) : [];
            $segmentRowsCount = count($data);
            return $segmentRowsCount;
        }
        elseif($cacheType == 'none'){
            return 0;
        }

    }

    /**
     * remove from segment cache
     *
     * @param \Illuminate\Database\Eloquent\Model $segment
     * @return bool
     */
    public function deleteFromAppGroupSegmentCache($segment)
    {

        try {

            $app_group_id = $segment->app_group_id;
            $segment_id = $segment->id;

            // load cache key
            $_key = new CacheKeys($app_group_id);
            $cache_key = $_key->generateAppGroupSegmentKey();

            // get cached segments
            $segments = \Cache::get($cache_key);

            if (isset($segments)) {
                $segments = \GuzzleHttp\json_decode($segments, true);
            } else {
                $segments = [];
            }

            $_segments = [];
            if (in_array($segment_id, $segments)) {

                foreach ($segments as $key => $val) {
                    if ($val != $segment_id) {
                        $_segments[] = $val;
                    }
                }

                self::removeEntry($cache_key);
                \Cache::forever($cache_key, \GuzzleHttp\json_encode($_segments));

                return true;
            }
        } catch (\Exception $exception) {
            Log::info($exception->getMessage());
        }

        return false;
    }

    /**
     * remove from segment rows cache
     *
     * @param \Illuminate\Database\Eloquent\Model $segment
     * @return bool
     */
    public function deleteFromAppGroupSegmentRowsCache($segment)
    {
        try {

            $app_group_id = $segment->app_group_id;
            $segment_id = $segment->id;

            // load cache key
            $_key = new CacheKeys($app_group_id);
            $cache_key = $_key->generateAppGroupSegmentRowsKey($segment_id);

            // get cached segments
            //$segments = \Cache::get($cache_key);

            self::removeEntry($cache_key);

        } catch (\Exception $exception) {
            Log::info($exception->getMessage());
        }

        return false;
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