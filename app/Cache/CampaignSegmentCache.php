<?php

namespace App\Cache;

use App\Campaign;
use App\CampaignSegment;
use App\Cache\CacheKeys;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CampaignSegmentCache
{

    /**
     * get all campaign segments and save into
     * campaign segment cache
     *
     * @param \Illuminate\Database\Eloquent\Model $campaign
     * @return bool
     */
    public function saveCampaignSegmentCache($campaign)
    {

        try {
            $campaign_id = $campaign->id;

            // load cache key
            $_key = new CacheKeys($campaign_id);
            $cache_key = $_key->generateCampaignSegmentKey($campaign_id);

            // get cached segments
            $campaign_segments = \Cache::get($cache_key);

            // get all campaign segments
            $campaign_segments = $this->getCampaignSegments($campaign_id);

            $campaign_segments = \GuzzleHttp\json_encode($campaign_segments);

            //self::removeEntry($cache_key);
            \Cache::forever($cache_key, $campaign_segments);
            //dump($campaign_segments);
            return $campaign_segments;

        } catch (\Exception $exception) {
            dd($exception);
        }
        return false;
    }

    /**
     * get campaign segments from
     * campaign segment cache
     *
     * @param int $campaign_id
     * @return array
     */
    public function getCampaignSegmentsCache($campaign_id)
    {

        // load cache key
        $_key = new CacheKeys();
        $cache_key = $_key->generateCampaignSegmentKey($campaign_id);

        // get cached segments
        $campaign_segments = \Cache::get($cache_key);

        if (!isset($campaign_segments)) {
            // get all campaign segments
            $campaign_segments = $this->getCampaignSegments($campaign_id);
        } else {
            $campaign_segments = \GuzzleHttp\json_decode($campaign_segments);
        }

        return $campaign_segments;
    }

    /**
     * get all campaign segment rows
     *
     * @param int $campaign_id
     *
     * @return @array $segment_ids
     */
    public function getCampaignSegments($campaign_id)
    {

        // empty initialize
        $_segments = [];
        $segment_ids = [];

        // getting all campaign segments
        $_segments = CampaignSegment::where('campaign_id', '=', $campaign_id)
            ->get();

        // loop through each segments
        foreach ($_segments as $segment) {
            $segment_ids[] = $segment->segment_id;
        }

        return $segment_ids;
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