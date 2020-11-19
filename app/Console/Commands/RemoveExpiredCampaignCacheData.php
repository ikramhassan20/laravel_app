<?php

namespace App\Console\Commands;

use App\Cache\CacheKeys;
use App\Campaign;
use App\CampaignTracking;
use App\Concerns\exportUsers;
use App\ExpiredCampaignStat;
use App\ExpiredCampaignTracking;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class RemoveExpiredCampaignCacheData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'expired-campaign:cache-remove';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to remove cache data for campaigns having status of expired and suspended';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            $campaigns = Campaign::without(['variants', 'variants.translations', 'segments', 'actions', 'schedules'])
                ->select(['id', 'app_group_id', 'status', 'is_remove_cache'])
                ->whereIn('status', [Campaign::STATUS_EXPIRED, Campaign::STATUS_SUSPENDED])
                ->where('is_remove_cache', 0)
                ->get();

            foreach ($campaigns as $campaign) {
                DB::beginTransaction();

                echo 'Removing cache data of campaign # ' . $campaign->id . '...' . PHP_EOL;

                $cachePrefix = config('cache.prefix');

                $key = new CacheKeys($campaign->app_group_id);
                $campaignSegmentCacheKey = $key->generateCampaignSegmentKey($campaign->id);
                $campaignSegmentUnionCacheKey = $key->generateCampaignSegmentsUnionCacheKey($campaign->id);
                $campaignTrackingCacheWildCard = $cachePrefix . ":campaign_tracking_campaign_id_" . $campaign->id . "_row_id_*";
                $campaignCappingCacheWildCard = $cachePrefix . ":app_group_id_" . $campaign->app_group_id . "_campaign_" . $campaign->id . "_row_*";

                RemoveExpiredCampaignCacheData::removeEntry($campaignSegmentCacheKey);
                RemoveExpiredCampaignCacheData::removeEntry($campaignSegmentUnionCacheKey);

                $this->removeWildCardCacheKeys($campaignTrackingCacheWildCard);
                $this->removeWildCardCacheKeys($campaignCappingCacheWildCard);

                $this->updateTrackingStats($campaign->id, $campaign->app_group_id);

                if (config('engagement.api.archive_campaign_tracking.enabled')) {
                    $this->archiveCampaignTrackingData($campaign->id);
                }

                $this->removeCampaignTrackingData($campaign->id);

                $campaign->update(['is_remove_cache' => 1]);

                DB::commit();
            }
            echo 'All Done' . PHP_EOL;

        } catch (\Exception $exception) {
            Log::info('Expired campaigns cache flush: '. $exception->getMessage());
            DB::rollBack();
        }
    }

    public function archiveCampaignTrackingData($campaignID)
    {
        echo 'Archiving campaign tracking data for campaign # ' . $campaignID . '...' . PHP_EOL;

        $chunkSize = config('engagement.api.archive_campaign_tracking.chunkSize');
        $campaignTrackings = CampaignTracking::where('campaign_id', $campaignID)
            ->chunk($chunkSize, function ($trackings) {
                ExpiredCampaignTracking::insert($trackings->toArray());
            });
    }

    public function removeCampaignTrackingData($campaignID)
    {
        DB::delete('DELETE FROM `campaign_tracking` WHERE `campaign_id` = ' . $campaignID);
    }

    public function updateTrackingStats($campaignID, $appGroupID)
    {

        $expiredCampaignStats = ExpiredCampaignStat::where('campaign_id', $campaignID)->first();
        if (empty($expiredCampaignStats)) {
            $expiredCampaignStats = new ExpiredCampaignStat();
            $expiredCampaignStats->campaign_id = $campaignID;
        }

        $totalQuery = DB::select("SELECT count(*) as total from campaign_tracking where campaign_id =" . $campaignID);
        $expiredCampaignStats->total_trackings = !empty($totalQuery[0]->total) ? $totalQuery[0]->total : 0;

        $totalSentQuery = DB::select("SELECT count(*) as sent from campaign_tracking where campaign_id =" . $campaignID . " AND sent <> 0");
        $expiredCampaignStats->total_sent = !empty($totalSentQuery[0]->sent) ? $totalSentQuery[0]->sent : 0;

        $totalViewedQuery = DB::select("SELECT count(*) as viewed from campaign_tracking where campaign_id =" . $campaignID . " AND viewed <> 0");
        $expiredCampaignStats->total_viewed = !empty($totalViewedQuery[0]->viewed) ? $totalViewedQuery[0]->viewed : 0;

        $expiredCampaignStats->total_unique_viewed = CampaignTracking::where('campaign_id', $campaignID)->where('viewed', '<>', 0)->distinct('row_id')->count('row_id');

        $failedQuery = DB::select("SELECT count(*) as failed from campaign_tracking where campaign_id =" . $campaignID . " AND status = 'failed'");
        $expiredCampaignStats->total_failed = !empty($failedQuery[0]->failed) ? $failedQuery[0]->failed : 0;

        $totalAndroidSentQuery = DB::select("SELECT count(*) as total from campaign_tracking where campaign_id =" . $campaignID . " AND device_type='android' AND sent <> 0");
        $expiredCampaignStats->total_android_sent = !empty($totalAndroidSentQuery[0]->total) ? $totalAndroidSentQuery[0]->total : 0;

        $totalAndroidViewedQuery = DB::select("SELECT count(*) as total from campaign_tracking where campaign_id =" . $campaignID . " AND device_type='android' AND viewed <> 0");
        $expiredCampaignStats->total_android_viewed = !empty($totalAndroidViewedQuery[0]->total) ? $totalAndroidViewedQuery[0]->total : 0;

        $totalAndroidFailedQuery = DB::select("SELECT count(*) as total from campaign_tracking where campaign_id =" . $campaignID . " AND device_type='android' AND status = 'failed'");
        $expiredCampaignStats->total_android_failed = !empty($totalAndroidFailedQuery[0]->total) ? $totalAndroidFailedQuery[0]->total : 0;

        $totalIOSSentQuery = DB::select("SELECT count(*) as total from campaign_tracking where campaign_id =" . $campaignID . " AND device_type='ios' AND sent <> 0");
        $expiredCampaignStats->total_ios_sent = !empty($totalIOSSentQuery[0]->total) ? $totalIOSSentQuery[0]->total : 0;

        $totalIOSViewedQuery = DB::select("SELECT count(*) as total from campaign_tracking where campaign_id =" . $campaignID . " AND device_type='ios' AND viewed <> 0");
        $expiredCampaignStats->total_ios_viewed = !empty($totalIOSViewedQuery[0]->total) ? $totalIOSViewedQuery[0]->total : 0;

        $totalIOSFailedQuery = DB::select("SELECT count(*) as total from campaign_tracking where campaign_id =" . $campaignID . " AND device_type='ios' AND status = 'failed'");
        $expiredCampaignStats->total_ios_failed = !empty($totalIOSFailedQuery[0]->total) ? $totalIOSFailedQuery[0]->total : 0;

        $expiredCampaignStats->targeted_users = exportUsers::exportUsers($campaignID, 'campaign', $appGroupID, true);

        $lastTenRowIDsQuery = DB::select("SELECT row_id FROM campaign_tracking where campaign_id=". $campaignID ." AND viewed > 0 ORDER BY viewed_at DESC limit 10");
        $lastTenRowIDs = [];
        foreach ($lastTenRowIDsQuery as $item){
            array_push($lastTenRowIDs, $item->row_id);
        }
        $expiredCampaignStats->last_ten_row_ids = !empty($lastTenRowIDs) ? json_encode($lastTenRowIDs) : NULL;

        $expiredCampaignStats->save();

    }

    public function removeWildCardCacheKeys($wildCardKey)
    {
        $limit = (!empty(config('engagement.api.limit.redis_cache_limit')) ? config('engagement.api.limit.redis_cache_limit') : 50000);
        $condition = true;
        $offset = 0;
        while ($condition) {
            $data = Redis::scan($offset, 'count', $limit, 'match', $wildCardKey);
            $offset = $data[0];
            $cacheKeys = $data[1];

            if (is_array($cacheKeys) && count($cacheKeys) > 0) {
                Redis::del($cacheKeys);
            }

            if ($offset == 0) {
                $condition = false;
            }
        }

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
