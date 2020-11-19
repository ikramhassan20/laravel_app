<?php

namespace App\Cache;

use App\Cache\CacheKeys;
use App\Campaign;
use App\CampaignSegment;
use App\Translation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CampaignTranslationCache
{
    /**
     * set campaign translation cache
     *
     * @param int $app_group_id
     * @param int $campaign_id
     * @param int $language_id
     * @param int $variant_id
     * @param mixed $data
     *
     * @return html_content
     */
    public static function setCampaignTranslationCache($app_group_id, $campaign_id, $language_id, $variant_id, $data)
    {
        try {

            // load cache key
            $_key = new CacheKeys($app_group_id);
            $cache_key = $_key->generateCampaignTranslationKey($campaign_id, $language_id, $variant_id);

            if (isset($data)) {

                $encodedData = \GuzzleHttp\json_encode($data);
                //self::removeEntry($cache_key);
                \Cache::forever($cache_key, $encodedData);

                return $encodedData;
            }
        } catch (\Exception $exception) {

        }

        return \GuzzleHttp\json_encode($data);
    }



    public static function setBoardTranslationCache($app_group_id, $board_id, $language_id, $variant_id, $data)
    {
        try {

            // load cache key
            $_key = new CacheKeys($app_group_id);
            $cache_key = $_key->generateBoardTranslationKey($app_group_id,$board_id, $language_id, $variant_id);

            if (isset($data)) {

                $encodedData = \GuzzleHttp\json_encode($data);
                //self::removeEntry($cache_key);
                \Cache::forever($cache_key, $encodedData);

                return $encodedData;
            }
        } catch (\Exception $exception) {

        }

        return \GuzzleHttp\json_encode($data);
    }


    public static function getBoardTranslationCache($app_group_id, $board_id, $language_id, $variant_step_id)
    {
        $cache_key = "";
        $board_translation = Translation::where('language_id', $language_id)
            ->where('translatable_id', $variant_step_id)
            ->where('translatable_type', 'board')
            ->first();

        if($board_translation){
            $cache_key = $board_translation->template;
        }

        // get cached campaign tracking
        $content_translation = \Cache::get($cache_key);

        if (isset($content_translation)) {
            return \GuzzleHttp\json_decode($content_translation);
        }

        return (isset($content_translation)) ? \GuzzleHttp\json_decode($content_translation) : "";

    }

    /**
     * get campaign translation cache
     *
     * @param int $app_group_id
     * @param int $campaign_id
     * @param int $language_id
     * @param int $variant_id
     *
     * @return html_content
     */
    public static function getCampaignTranslationCache($app_group_id, $campaign_id, $language_id, $variant_id)
    {
        // load cache key
        //$_key = new CacheKeys($app_group_id);
        //$cache_key = $_key->generateCampaignTranslationKey($campaign_id, $language_id, $variant_id);

        $cache_key = "";
        $campaign_translation = Translation::where('language_id', $language_id)
                                                ->where('translatable_id', $variant_id)
                                                ->where('translatable_type', 'campaign')
                                                ->first();
        if($campaign_translation){
            $cache_key = $campaign_translation->template;
        }

        // get cached campaign tracking
        $content_translation = \Cache::get($cache_key);

        if (isset($content_translation)) {
            return \GuzzleHttp\json_decode($content_translation);
        }

        return (isset($content_translation)) ? \GuzzleHttp\json_decode($content_translation) : "";
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