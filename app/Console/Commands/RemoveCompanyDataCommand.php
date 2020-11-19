<?php

namespace App\Console\Commands;

use App\AppGroup;
use App\Apps;
use App\AppUserActivity;
use App\AppUsers;
use App\BoardTracking;
use App\Cache\AppUserLoginSignupCache;
use App\Cache\CacheKeys;
use App\CampaignTracking;
use App\ImportData;
use App\Jobs\RebuildCacheJob;
use App\LinkTrackings;
use App\LocationArea;
use App\NewsFeedImpression;
use App\Segment;
use App\Translation;
use App\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\Console\Input\InputArgument;

class RemoveCompanyDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'company:remove';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove all data for specific company';

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
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '2048M');

        $id = $this->argument('id');
        try {
            $company = User::find($id);
            if (empty($company)) {
                echo 'Company not found.';
                exit;
            }

            $groups = AppGroup::select('id')->with([
                'locations' => function ($q) {
                    $q->select('id', 'app_group_id');
                }, 'newsFeeds' => function ($q) {
                    $q->select('id', 'app_group_id');
                }, 'segments' => function ($q) {
                    $q->select('id', 'app_group_id');
                }, 'campaigns' => function ($q) {
                    $q->without(['segments', 'schedules', 'actions', 'variants', 'variants.translations']);
                    $q->select('id', 'app_group_id');
                }, 'boards' => function ($q) {
                    $q->select('id', 'app_group_id');
                }])
                ->where('company_id', $id)
                ->get();

            $campaignIDs = [];
            $boardIDs = [];
            $appGroupIDs = [];
            $cachePrefix = config('cache.prefix');

            echo 'Removing Cache data for Company #: ' . $id . PHP_EOL;

            foreach ($groups as $group) {
                array_push($appGroupIDs, $group->id);

                $segments = $group->segments;
                $campaigns = $group->campaigns;
                $boards = $group->boards;

                $key = new CacheKeys($group->id);

                $appGroupRelatedKeys = $cachePrefix . ":app_group_id_" . $group->id . "_*";
                $dashboardRelatedKeys = $cachePrefix . ":dashboard_stats_app_group_id_" . $group->id . "_*";

                $this->removeWildCardCacheKeys($appGroupRelatedKeys);
                $this->removeWildCardCacheKeys($dashboardRelatedKeys);

                $processExportUserKey = $key->generateProcessExportUsersKey($group->id);
                $exportUserKey = $key->generateProcessExportUsersKey($group->id);

                AppUserLoginSignupCache::removeEntry($processExportUserKey);
                AppUserLoginSignupCache::removeEntry($exportUserKey);

                foreach ($campaigns as $campaign) {
                    array_push($campaignIDs, $campaign->id);

                    $campaignSegmentCacheKey = $key->generateCampaignSegmentKey($campaign->id);
                    AppUserLoginSignupCache::removeEntry($campaignSegmentCacheKey);

                    $campaignTrackingCacheWildCard = $cachePrefix . ":campaign_tracking_campaign_id_" . $campaign->id . "_row_id_*";
                    $this->removeWildCardCacheKeys($campaignTrackingCacheWildCard);

                }

                foreach ($boards as $board) {
                    array_push($boardIDs, $board->id);
                    $boardSegmentCacheKey = $key->generateBoardSegmentKey($board->id);
                    AppUserLoginSignupCache::removeEntry($boardSegmentCacheKey);

                    $boardTrackingCacheWildCard = $cachePrefix . ":board_tracking_board_id_" . $board->id . "_row_id_*";
                    $this->removeWildCardCacheKeys($boardTrackingCacheWildCard);
                }
            }
            echo 'Removing DB data for Company #: ' . $id . PHP_EOL;
            \DB::beginTransaction();

            foreach ($groups as $group) {

                $newsFeeds = $group->newsFeeds->toArray();
                $newsFeedIDs = [];
                if (!empty($newsFeeds)) {
                    $newsFeedIDs = array_column($newsFeeds, 'id');
                }

                $locations = $group->locations->toArray();
                $locationIDs = [];
                if (!empty($locations)) {
                    $locationIDs = array_column($locations, 'id');
                }

                // Deleting News Feed Impressions
                NewsFeedImpression::whereIn('news_feed_id', $newsFeedIDs)->forceDelete();

                //Deleting Link Tracking
                LinkTrackings::whereIn('rec_id', $newsFeedIDs)->forceDelete();

                // Deleting Location Areas
                LocationArea::whereIn('location_id', $locationIDs)->forceDelete();

                // Deleting Translations
                Translation::whereIn('translatable_id', $campaignIDs)->where('translatable_type', 'campaign')->delete();
                Translation::whereIn('translatable_id', $boardIDs)->where('translatable_type', 'board')->delete();
                Translation::whereIn('translatable_id', $newsFeedIDs)->where('translatable_type', 'newsfeed')->delete();

                // Deleting Campaign Tracings
                CampaignTracking::whereIn('campaign_id', $campaignIDs)->forceDelete();

                // Deleting Board Tracings
                BoardTracking::whereIn('board_id', $boardIDs)->forceDelete();

                $segments = $group->segments;

                // Deleting Segments
                foreach ($segments as $segment) {
                    $segment->delete();
                }

                // Deleting App Group
                $group->delete();
            }

            // Deleting App User Activities and APP Users
            AppUserActivity::whereIn('campaign_id', $campaignIDs)->where('resource_type', 'campaign')->forceDelete();
            AppUserActivity::whereIn('campaign_id', $boardIDs)->where('resource_type', 'board')->forceDelete();
            AppUsers::whereIn('app_group_id', $appGroupIDs)->where('company_id', $id)->forceDelete();


            // Deleting Import Data
            ImportData::where('company_id', $id)->delete();

            // Deleting Company
            $company->delete();

            \DB::commit();

            echo 'Company removed successfully.';

        } catch (\Exception $exp) {
            \DB::rollBack();
            $this->rebuildCache($id);

            echo 'Company could not removed. Error Occurred: ' . $exp->getMessage();

        }

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

    public function rebuildCache($id)
    {
        try {
            $company = User::find($id);
            $company->update([
                'cache_status' => 'inprocess'
            ]);

            RebuildCacheJob::dispatch($company)->onQueue('rebuild_cache')->delay(Carbon::now()->addSeconds(10));

            echo 'Rebuild cache successfully!';
        } catch (\Exception $exp) {
            echo 'Rebuild cache Failed!';
        }
    }

    protected function configure()
    {
        $this->addArgument('id', InputArgument::REQUIRED, 'Company ID');
    }
}
