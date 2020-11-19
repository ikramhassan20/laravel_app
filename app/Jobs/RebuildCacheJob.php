<?php

namespace App\Jobs;

use App\AppGroup;
use App\AppUsers;
use App\Board;
use App\Cache\AppGroupSegmentCache;
use App\Cache\AppUserLoginSignupCache;
use App\Cache\BoardSegmentCache;
use App\Cache\CacheKeys;
use App\Cache\CampaignSegmentCache;
use App\Cache\OnceBoardRowIdsCache;
use App\Campaign;
use app\Helpers\CommonHelper;
use App\Segment;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Mockery\Exception;

class RebuildCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $company;

    public $timeout = 0;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($company)
    {
        $this->company = $company;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        try {
            ini_set('max_execution_time', 0); //600 seconds = 10 minutes
            ini_set('memory_limit', '2048M');

            $limit = config('engagement.api.headers.limit');

            $company = $this->company;

            $app_users = AppUsers::select(['row_id', 'user_id', 'app_id', 'app_group_id'])
                ->where('company_id', $company->id)
                ->where('is_deleted', 0)
                ->where('deleted_at', NULL)
                ->get()->chunk($limit);
            if (count($app_users) > 0) {
                foreach ($app_users as $users) {
                    foreach ($users as $user) {
                        // app_group_id_100_row_id_500
                        $cache = new AppUserLoginSignupCache();
                        $cache->saveAppUserSignupCache([
                            'user_id' => $user->user_id,
                            'app_id' => $user->app_id,
                            'company_id' => $company->id,
                            'app_group_id' => $user->app_group_id,
                            'mode' => AppUsers::USER_REBUILD_CACHE
                        ]);
                    }
                    Log::info(" app users count: " . count($users) . "\n");
                }
            }

            /* $groups = AppGroup::with('segments', 'segments.campaigns')
                ->where('company_id', $company->id)
                ->get(); */

            $groups = DB::select("SELECT id FROM app_group where company_id = {$company->id}");

            $appGroupIDs = [];
            if (count($groups) > 0) {
                foreach ($groups as $group) {
                    $_key = new CacheKeys($group->id);
                    $cache_key = $_key->generateAppGroupSegmentKey();
//                AppGroupSegmentCache::removeEntry($cache_key);
                    array_push($appGroupIDs, $group->id);
                }
            }

            if (!empty($appGroupIDs)) {

                $appGroupIDs = implode(',', $appGroupIDs);

                $segments = DB::select("SELECT id, app_group_id, is_active FROM segment where app_group_id IN ({$appGroupIDs})");

                foreach ($segments as $segment) {

                    $cache = new AppGroupSegmentCache();
                    $cache->saveAppGroupSegmentRowsCache($segment, true);

                    // app_group_id_1_segments
                    $segment_cache = new AppGroupSegmentCache();
                    $segment_cache->saveAppGroupSegmentCache($segment);
                }

                $this->updateCampaignsCache($appGroupIDs);
                $this->updateBoardsCache($appGroupIDs);
            }

            /*  if (count($groups) > 0) {
                  foreach ($groups as $group) {
                      $segments = $group->segments;

                      $_key = new CacheKeys($group->id);
                      $cache_key = $_key->generateAppGroupSegmentKey();
                      AppGroupSegmentCache::removeEntry($cache_key);

                      foreach ($segments as $segment) {
                          // app_group_id_1_segment_1_rows
                          $_key = new CacheKeys($segment->app_group_id);
                          $cache_key = $_key->generateAppGroupSegmentRowsKey($segment->id);
                          AppGroupSegmentCache::removeEntry($cache_key);

                          $cache = new AppGroupSegmentCache();
                          $cache->saveAppGroupSegmentRowsCache($segment, true);

                          // app_group_id_1_segments
                          $segment_cache = new AppGroupSegmentCache();
                          $segment_cache->saveAppGroupSegmentCache($segment);

                          $campaigns = $segment->campaigns;
                          foreach ($campaigns as $campaign) {
                              $campaignCache = new CampaignSegmentCache();
                              $campaignCache->saveCampaignSegmentCache($campaign);
                          }
                      }
                  }
              } */


            $company->update([
                'cache_status' => 'completed'
            ]);
        } catch (Exception $e) {
            echo $e->getMessage();
        }

    }

    public function updateCampaignsCache($appGroupIDs)
    {
        $campaigns = DB::select("SELECT id, app_group_id, status FROM campaign where app_group_id IN ({$appGroupIDs})");
        foreach ($campaigns as $campaign) {
            $campaignCache = new CampaignSegmentCache();
            $campaignCache->saveCampaignSegmentCache($campaign);

            if (!in_array($campaign->status, [Campaign::STATUS_SUSPENDED, Campaign::STATUS_EXPIRED])) {
                $campaignSegments = [];
                $_campaign = new CampaignSegmentCache();
                $campaignSegments = $_campaign->getCampaignSegmentsCache($campaign->id);
                // new code paging/scan changes
                $objCachekey = new CacheKeys($campaign->app_group_id);
                $cache_prefix = config('cache.prefix');
                $unionStoreKey = $cache_prefix . ":" . $objCachekey->generateCampaignSegmentsUnionCacheKey($campaign->id);
                $unionCacheKeys = \App\Helpers\CommonHelper::getSegmentsUnionCacheKeys($campaignSegments, $campaign->app_group_id);
                if (sizeof($campaignSegments) > 0) {
                    Redis::sunionstore($unionStoreKey, $unionCacheKeys);
                }
            }
        }
    }

    public function updateBoardsCache($appGroupIDs)
    {
        $boards = DB::select("SELECT id, app_group_id, schedule_type, status FROM board where app_group_id IN ({$appGroupIDs})");
        foreach ($boards as $board) {
            $board_segment_cache = new BoardSegmentCache();
            $board_segment_cache->saveBoardSegmentCache($board);

            if (!in_array($board->status, [Board::STATUS_EXPIRED, Board::STATUS_SUSPENDED])) {

                $cacheKey = new CacheKeys($board->app_group_id);
                $cache_prefix = config('cache.prefix');

                $boardSegmentCache = new BoardSegmentCache();
                $boardSegments = $boardSegmentCache->getBoardSegmentsCache($board->id);

                $unionStoreKey = $cache_prefix . ":" . $cacheKey->generateBoardSegmentsUnionCacheKey($board->id);
                $unionCacheKeys = \App\Helpers\CommonHelper::getSegmentsUnionCacheKeys($boardSegments, $board->app_group_id);

                $objOnceBoardCache = new OnceBoardRowIdsCache();

                if ($board->schedule_type == Board::SCHEDULE_ONCE) {
                    $onceBoardCache = $objOnceBoardCache->getOnceBoardRowIdsCache($board->app_group_id, $board->id);
                    if ($onceBoardCache) {
                        $cache_refresh = false;
                    } else {
                        $cache_refresh = true;
                    }
                } else {
                    $cache_refresh = true;
                }

                if ($cache_refresh === true) {
                    Redis::sunionstore($unionStoreKey, $unionCacheKeys);
                }

                $isKey = (bool)Redis::exists($unionStoreKey);
                if ($isKey === true) {
                    $condition = true;
                    $offset = 0;
                    $limit = (!empty(config('engagement.api.limit.redis_cache_limit')) ? config('engagement.api.limit.redis_cache_limit') : 50000);
                    while ($condition) {
                        $readData = \App\Helpers\CommonHelper::readDataFromRedisUnionCache($unionStoreKey, $limit, $offset);
                        $board_segment_rows = $readData['rowIds'];
                        $offset = $readData['offset'];

                        if ($board_segment_rows !== null && isset($board_segment_rows) && sizeof($board_segment_rows) > 0) {
                            if ($cache_refresh == true) {
                                $objOnceBoardCache->saveOnceBoardRowIdsCache($board->app_group_id, $board->id, $board_segment_rows);
                            }
                        }

                        if ($readData['paging'] === false || $readData['offset'] == 0) {
                            $condition = false;
                        }
                    }

                }
            }
        }

    }
}
