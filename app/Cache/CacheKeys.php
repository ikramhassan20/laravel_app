<?php

namespace App\Cache;


class CacheKeys
{

    /**
     * Initialize with app_group_id
     *
     * @param int app_group_id
     */
    public function __construct($app_group_id = NULL)
    {
        $this->app_group_id = $app_group_id;
    }

    /**
     * generate application segment cache key
     *
     * @return string segment_key
     */
    public function generateAppGroupSegmentKey()
    {
        // build app group segment key
        $segment_key = "app_group_id_" . $this->app_group_id . "_segments";

        return $segment_key;
    }

    /**
     * generate application segment rows cache key
     *
     * @param int segment_id
     * @return string segment_key
     */
    public function generateAppGroupSegmentRowsKey($segment_id)
    {
        // build app group segment rows key
        $segment_row_key = "app_group_id_" . $this->app_group_id . "_segment_" . $segment_id . "_rows";

        return $segment_row_key;
    }

    /**
     * generate application segment rows cache key
     *
     * @param int segment_id
     * @return string segment_key
     */
    public function generateAppUserLoginSignupKey($row_id)
    {
        // build app group segment rows key
        $app_user_key = "app_group_id_" . $this->app_group_id . "_row_id_" . $row_id;

        return $app_user_key;
    }

    /**
     * generate application segment rows cache key
     *
     * @param int segment_id
     * @return string segment_key
     */
    public function generateCampaignSegmentKey($campaign_id)
    {
        // build app group segment rows key
        $campaign_segment_key = "campaign_" . $campaign_id . "_segments";

        return $campaign_segment_key;
    }

    /**
     * generate application segment rows cache key
     *
     * @param int segment_id
     * @return string segment_key
     */
    public function generateBoardSegmentKey($board_id)
    {
        // build app group segment rows key
        $board_segment_key = "board_" . $board_id . "_segments";

        return $board_segment_key;
    }

    /**
     * generate application tracking cache key
     *
     * @param int $campaign_id
     * @param int $token_id
     *
     * @return string campaign_tracking_key
     */
    public function generateCampaignTrackingKey($campaign_id, $row_id, $language, $variant_id)
    {
        // build app group segment rows key
        $campaign_tracking_key = "campaign_tracking_campaign_id_" . $campaign_id . "_row_id_" . $row_id . '_language_' . $language . "_variant_" . $variant_id;

        return $campaign_tracking_key;
    }


    /**
     * Function used to generate board cache key
     *
     * @param $board_id
     * @param $row_id
     * @param $language
     * @param $variant_step_id
     * @return string $board_tracking_key
     */
    public function generateBoardTrackingKey($board_id, $row_id, $language, $variant_step_id)
    {
        // build app group segment rows key
        $board_tracking_key = "board_tracking_board_id_" . $board_id . "_row_id_" . $row_id . '_language_' . $language . "_variant_step_id" . $variant_step_id;
        
        return $board_tracking_key;
    }


    /**
     * Function used to generate board user cache key
     * @param $board_id
     * @param $row_id
     * @return string $board_user_tracking_key
     */
    public function generateBoardUserTrackingKey($board_id, $row_id)
    {
        $board_user_tracking_key = "board_user_tracking_board_id_" . $board_id . "_row_id_" . $row_id;

        return $board_user_tracking_key;

    }


    /**
     * Function used to generate Once board rowIds cache key
     * @param int $board_id
     * @return string
     */
    public function generateOnceTypeBoardRowIdsCacheKey($board_id)
    {
        $onceTypeBoardCacheKey = 'app_group_id_'.$this->app_group_id.'_once_board_id_'.$board_id.'_rows';
        return $onceTypeBoardCacheKey;
    }


    /**
     * Function used to generate board segments union cache key
     * @param $board_id
     * @return string
     */
    public function generateBoardSegmentsUnionCacheKey($board_id)
    {
        $boardSegmentsUnionKey = 'app_group_id_'.$this->app_group_id.'_board_id_'.$board_id.'_segments_union';
        return $boardSegmentsUnionKey;

    }


    /**
     * Function used to generate campaign segments union cache key
     * @param $campaign_id
     * @return string
     */
    public function generateCampaignSegmentsUnionCacheKey($campaign_id)
    {
        $campaignSegmentsUnionKey = 'app_group_id_'.$this->app_group_id.'_campaign_id_'.$campaign_id.'_segments_union';
        return $campaignSegmentsUnionKey;

    }

    /**
     * generate application campaign translation cache key
     *
     * @param int $campaign_id
     * @param int $language_id
     * @param int $variant_id
     *
     * @return string campaign_translation_key
     */
    public function generateCampaignTranslationKey($campaign_id, $language_id, $variant_id)
    {
        // build app group segment rows key
        $campaign_translation_key = "app_group_id_" . $this->app_group_id . "_campaign_" . $campaign_id . "_language_" . $language_id . "_variant_" . $variant_id;

        return $campaign_translation_key;
    }

    public function generateBoardTranslationKey($app_group_id, $boardId, $language_id, $variant_id)
    {
        // build app group segment rows key
        $campaign_translation_key = "app_group_id_" . $app_group_id . "_board_" . $boardId . "_language_" . $language_id . "_variant_" . $variant_id;

        return $campaign_translation_key;
    }

    /**
     * generate application campaign capping cache key
     *
     * @param int $app_group_id_
     * @param int $campaign_id
     * @param int $row_id
     * @param string $language_code
     * @param int $variant_id
     *
     * @return string campaign_capping_key
     */
    public function generateCampaignCappingCacheKey($campaign_id, $row_id, $language_code, $variant_id)
    {
        $capping_cache = "app_group_id_" . $this->app_group_id . "_campaign_" . $campaign_id . "_row_" . $row_id . "_language_" . $language_code . "_variant_" . $variant_id . "_caprule";

        return $capping_cache;
    }

    /**
     * generate company export users cache key
     *
     * @param int $company_id
     *
     * @return string $export_users_key
     */
    public function generateExportUsersKey($groupId)
    {
        $export_users_key = "export_users_app_group_id_" . $groupId . "_csv";

        return $export_users_key;
    }

    public function generateProcessExportUsersKey($groupId)
    {
        $export_users_key = "process_export_users_app_group_id_" . $groupId . "_csv";

        return $export_users_key;
    }

    public function generateAppUserStatsKey()
    {
        $app_users = "dashboard_stats_app_group_id_" . $this->app_group_id . "_users";

        return $app_users;
    }

    public function generateCampaignStatsKey()
    {
        $campaign_stats = "dashboard_stats_app_group_id_" . $this->app_group_id . "_campaigns";

        return $campaign_stats;
    }

    public function generateCampaignConversionStatsKey()
    {
        $campaign_stats = "dashboard_stats_app_group_id_" . $this->app_group_id . "_campaign_conversion";

        return $campaign_stats;
    }

    public function generateNewsfeedViewsStatsKey()
    {
        $campaign_stats = "dashboard_stats_app_group_id_" . $this->app_group_id . "_newsfeed_views";

        return $campaign_stats;
    }

    public function generateNewsfeedClicksStatsKey()
    {
        $campaign_stats = "dashboard_stats_app_group_id_" . $this->app_group_id . "_newsfeed_clicks";

        return $campaign_stats;
    }

    public function generatePopularAppsCacheKey()
    {
        $popular_apps = "dashboard_stats_app_group_id_" . $this->app_group_id . "_popular_apps";

        return $popular_apps;
    }
}