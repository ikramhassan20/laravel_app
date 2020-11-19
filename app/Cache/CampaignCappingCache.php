<?php
namespace App\Cache;

use App\Campaign;
use App\CampaignSegment;
use App\Cache\CacheKeys;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CampaignCappingCache{

    /**
     * add capping rule data
     *
     * @param \Illuminate\Database\Eloquent\Model $campaign
     * @param int $row_id
     * @param string $language_code
     * @param int $variant_id
     *
     * @return data
     */
    public function capping_rule_data($campaign, $row_id, $language_code, $variant_id){

        // load cache key
        $_key = new CacheKeys($campaign->app_group_id);
        $cache_key = $_key->generateCampaignCappingCacheKey($campaign->id, $row_id, $language_code, $variant_id);

        $data = \Cache::get($cache_key);

        if (empty($data)) { return []; }

        $data = \GuzzleHttp\json_decode($data, true);

        return !empty($data) ? $data : [];
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $campaign
     * @param int $row_id
     * @param string $language_code
     * @param int $variant_id
     *
     * @return array
     */
    public static function getCappingCacheData($campaign, $row_id, $language_code, $variant_id)
    {
        // load cache key
        $_key = new CacheKeys($campaign->app_group_id);
        $cache_key = $_key->generateCampaignCappingCacheKey($campaign->id, $row_id, $language_code, $variant_id);

        $data = \Cache::get($cache_key);

        return !empty($data) ? \GuzzleHttp\json_decode($data, true) : [];
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $campaign
     * @param int   $row_id
     * @param array $data
     * @param string $language_code
     * @param int $variant_id
     *
     * @return void
     */
    public static function setCappingCacheData($campaign, $row_id, $data, $language_code, $variant_id)
    {
        // load cache key
        $_key = new CacheKeys($campaign->app_group_id);
        $cache_key = $_key->generateCampaignCappingCacheKey($campaign->id, $row_id, $language_code, $variant_id);

        \Cache::forever($cache_key, \GuzzleHttp\json_encode($data));
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