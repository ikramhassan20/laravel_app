<?php
namespace App\Cache;

use App\Campaign;
use App\CampaignSegment;
use App\Cache\CacheKeys;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CampaignTrackingCache{

    /**
     * add campaign tracking contents into
     * campaign tracking cache
     *
     * @param int $campaign_id
     * @param int $row_id
     * @param string $language
     * @param date $last_sent_date
     * @param int $sent_count
     *
     * @return bool
     */
    public function addCampaignTrackingCache($campaign_id, $row_id, $language, $variant_id, $content){

        try {

            // load cache key
            $_key = new CacheKeys(NULL);
            $cache_key = $_key->generateCampaignTrackingKey($campaign_id, $row_id, $language, $variant_id);

            // get cached campaign tracking
            $campaign_tracking = \Cache::get($cache_key);

            // get all campaign tracking
            $campaign_tracking = array('campaign_id' => $campaign_id,
                                        'row_id' => $row_id,
                                        'language' => $language,
                                        'content'  => $content);

            if (isset($campaign_tracking)) {

                //self::removeEntry($cache_key);
                \Cache::forever($cache_key, \GuzzleHttp\json_encode($campaign_tracking));

                return \GuzzleHttp\json_encode($campaign_tracking);
            }
        } catch (\Exception $exception) {

        }

        return false;
    }

    /**
     * update campaign tracking contents into
     * campaign tracking cache
     *
     * @param int $campaign_id
     * @param int $row_id
     * @param string $language
     * @param string $content
     * @param date $last_sent_date
     * @param int $sent_count
     *
     * @return bool
     */
    public function updateCampaignTrackingCache($campaign_id, $row_id, $language, $variant_id, $content, $last_sent_date, $sent_count){

        try {

            // load cache key
            $_key = new CacheKeys(NULL);
            $cache_key = $_key->generateCampaignTrackingKey($campaign_id, $row_id, $language, $variant_id);

            // get cached campaign tracking
            $campaign_tracking = \Cache::get($cache_key);

            /*$_tracking = []; $content = "";
            if(isset($campaign_tracking)){
                $_tracking = \GuzzleHttp\json_decode($campaign_tracking);
                $content = (isset($_tracking['content'])) ? $_tracking['content'] : "";
            }*/

            // get all campaign tracking
            $campaign_tracking = array(
                'campaign_id' => $campaign_id,
                'row_id' => $row_id,
                'language' => $language,
                'content'     => $content,
                'last_sent_date' => $last_sent_date,
                'sent_count' => $sent_count
            );

            if (isset($campaign_tracking)) {

                //self::removeEntry($cache_key);
                \Cache::forever($cache_key, \GuzzleHttp\json_encode($campaign_tracking));

                return \GuzzleHttp\json_encode($campaign_tracking);
            }
        } catch (\Exception $exception) {

        }

        return false;
    }

    /**
     * get campaign tracking cache
     *
     * @param int $campaign_id
     * @param int $row_id
     * @param int $language
     *
     * @return array
     */
    public function getCampaignTrackingCache($campaign_id, $row_id, $language, $variant_id){

        // load cache key
        $_key = new CacheKeys(NULL);
        $cache_key = $_key->generateCampaignTrackingKey($campaign_id, $row_id, $language, $variant_id);

        // get cached campaign tracking
        $campaign_tracking = \Cache::get($cache_key);

        if (isset($campaign_tracking)) {
            return \GuzzleHttp\json_decode($campaign_tracking);
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

        \Cache::forget($cache_key);
    }

}