<?php

namespace App\Cache;

use App\BoardSegment;
use App\Cache\CacheKeys;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BoardSegmentCache
{

    public function saveBoardSegmentCache($board)
    {
        try {
            $board_id = $board->id;

            // load cache key
            $_key = new CacheKeys($board_id);
            $cache_key = $_key->generateBoardSegmentKey($board_id);

            //remove board segment cache if exists
            //$this->removeEntry($cache_key);

            // get all board segments
            $board_segments = $this->getBoardSegments($board_id);

            $board_segments = \GuzzleHttp\json_encode($board_segments);

            \Cache::forever($cache_key, $board_segments);

            return $board_segments;

        } catch (\Exception $exception) {
            dd($exception);
        }
        return false;
    }

    public function getBoardSegmentsCache($board_id)
    {
        // load cache key
        $_key = new CacheKeys();
        $cache_key = $_key->generateBoardSegmentKey($board_id);

        // get cached segments
        $board_segments = \Cache::get($cache_key);

        if (!isset($board_segments)) {
            // get all board segments
            $board_segments = $this->getBoardSegments($board_id);
        }
        else{
            $board_segments = \GuzzleHttp\json_decode($board_segments);
        }

        return $board_segments;
    }

    /**
     * get all board segment rows
     *
     * @param int $board_id
     *
     * @return @array $segment_ids
     */
    public function getBoardSegments($board_id)
    {

        // empty initialize
        $_segments = [];
        $segment_ids = [];

        // getting all board segments
        $_segments = BoardSegment::where('board_id', '=', $board_id)
            ->get();

        // loop through each segments
        foreach ($_segments as $segment) {
            $segment_ids[] = $segment->segment_id;
        }

        return $segment_ids;
    }


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
