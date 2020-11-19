<?php

namespace App\Console\Commands;

use App\Cache\CacheKeys;
use App\Campaign;
use App\CampaignTracking;
use App\Events\CampaignSegmentCacheEvent;
use App\Language;
use App\Translation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\Console\Input\InputArgument;

class ArchiveCampaignCacheData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaign:remove';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to remove campaign specific cache data';

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
            $id = $this->argument('id');

            $campaignData = Campaign::without(['variants', 'segments', 'actions', 'schedules'])->select('app_group_id')
                ->where('id', $id)
                ->first();
            if ($campaignData) {
                echo 'Removing cache of Campaign no ' . $id . '.....' . PHP_EOL;
                DB::beginTransaction();
                $cachePrefix = config('cache.prefix');
                $key = new CacheKeys($campaignData->app_group_id);
                $campaignSegmentCacheKey = $key->generateCampaignSegmentKey($id);
                $campaignSegmentUnionCacheKey = $key->generateCampaignSegmentsUnionCacheKey($id);
                $campaignTrackingCacheWildCard = $cachePrefix . ":campaign_tracking_campaign_id_" . $id . "_row_id_*";
                $campaignCappingCacheWildCard = $cachePrefix . ":app_group_id_" . $campaignData->app_group_id . "_campaign_" . $id . "_*";

                RemoveExpiredCampaignCacheData::removeEntry($campaignSegmentCacheKey);
                RemoveExpiredCampaignCacheData::removeEntry($campaignSegmentUnionCacheKey);

                $this->removeWildCardCacheKeys($campaignTrackingCacheWildCard);
                $this->removeWildCardCacheKeys($campaignCappingCacheWildCard);

                echo 'Removing DB data of Campaign no ' . $id . '.....' . PHP_EOL;

                $this->removeCampaignTrackingData($id);
                Translation::where('translatable_id', $id)->where('translatable_type', 'campaign')->forceDelete();
                Campaign::where('id', $id)->forceDelete();

                DB::commit();

                echo 'Campaign Removed Successfully'. PHP_EOL;
            } else {
                echo 'Campaign not found!'. PHP_EOL;
            }

        } catch (\Exception $e) {
            DB::rollBack();
            echo $e->getMessage();
        }
    }

    public function removeCampaignTrackingData($campaignID)
    {
        DB::delete('DELETE FROM `campaign_tracking` WHERE `campaign_id` = ' . $campaignID);
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

        //\Redis::del($cache_key);
        \Cache::forget($cache_key);
    }

    protected function configure()
    {
        $this->addArgument('id', InputArgument::REQUIRED, 'Campaign ID');
    }
}
