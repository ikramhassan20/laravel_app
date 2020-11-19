<?php

namespace App\Console\Commands;

use App\Board;
use App\BoardTracking;
use App\Cache\CacheKeys;
use App\ExpiredBoardStat;
use App\ExpiredBoardTracking;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class RemoveExpiredBoardCacheData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'expired-board:cache-remove';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to remove cache data for boards having status of expired and suspended';

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
            $boards = Board::select(['id', 'app_group_id', 'status', 'is_remove_cache'])
                ->whereIn('status', [Board::STATUS_EXPIRED, Board::STATUS_SUSPENDED])
                ->where('is_remove_cache', 0)
                ->get();

            foreach ($boards as $board) {
                DB::beginTransaction();

                echo 'Removing cache data of board # ' . $board->id . '...' . PHP_EOL;

                $cachePrefix = config('cache.prefix');

                $key = new CacheKeys($board->app_group_id);
                $boardSegmentCacheKey = $key->generateBoardSegmentKey($board->id);
                $boardSegmentUnionCacheKey = $key->generateBoardSegmentsUnionCacheKey($board->id);
                $boardOnceTypeCacheKey = $key->generateOnceTypeBoardRowIdsCacheKey($board->id);
                $boardTrackingCacheWildCard = $cachePrefix . ":board_tracking_board_id_" . $board->id . "_row_id_*";
                $boardUserTrackingCacheWildCard = $cachePrefix . ":board_user_tracking_board_id_" . $board->id . "_row_id_*";
                $boardCappingCacheWildCard = $cachePrefix . ":app_group_id_" . $board->app_group_id . "_board_" . $board->id . "_row_*";

                RemoveExpiredboardCacheData::removeEntry($boardSegmentCacheKey);
                RemoveExpiredboardCacheData::removeEntry($boardSegmentUnionCacheKey);
                RemoveExpiredboardCacheData::removeEntry($boardOnceTypeCacheKey);

                $this->removeWildCardCacheKeys($boardTrackingCacheWildCard);
                $this->removeWildCardCacheKeys($boardUserTrackingCacheWildCard);
                $this->removeWildCardCacheKeys($boardCappingCacheWildCard);

                $this->updateTrackingStats($board->id, $board->app_group_id);

                if (config('engagement.api.archive_board_tracking.enabled')) {
                    $this->archiveBoardTrackingData($board->id);
                }

                $this->removeBoardTrackingData($board->id);

                $board->update(['is_remove_cache' => 1]);

                DB::commit();
            }
            echo 'All Done' . PHP_EOL;

        } catch (\Exception $exception) {
            Log::info('Expired boards cache flush: ' . $exception->getMessage());
            DB::rollBack();
        }
    }


    public function archiveBoardTrackingData($boardID)
    {
        echo 'Archiving board tracking data for board # ' . $boardID . '...' . PHP_EOL;

        $chunkSize = config('engagement.api.archive_board_tracking.chunkSize');

        $boardTrackings = BoardTracking::where('board_id', $boardID)
            ->chunk($chunkSize, function ($trackings) {
                ExpiredBoardTracking::insert($trackings->toArray());
            });
    }

    public function removeBoardTrackingData($boardID)
    {
        DB::delete('DELETE FROM `board_tracking` WHERE `board_id` = ' . $boardID);
    }

    public function updateTrackingStats($boardID, $appGroupID)
    {

        $expiredBoardStats = ExpiredBoardStat::where('board_id', $boardID)->first();
        if (empty($expiredBoardStats)) {
            $expiredBoardStats = new ExpiredBoardStat();
            $expiredBoardStats->board_id = $boardID;
        }

        $totalQuery = DB::select("SELECT count(*) as total from board_tracking where board_id =" . $boardID);
        $expiredBoardStats->total_trackings = !empty($totalQuery[0]->total) ? $totalQuery[0]->total : 0;

        $totalSentQuery = DB::select("SELECT count(*) as sent from board_tracking where board_id =" . $boardID . " AND sent <> 0");
        $expiredBoardStats->total_sent = !empty($totalSentQuery[0]->sent) ? $totalSentQuery[0]->sent : 0;

        $totalViewedQuery = DB::select("SELECT count(*) as viewed from board_tracking where board_id =" . $boardID . " AND viewed <> 0");
        $expiredBoardStats->total_viewed = !empty($totalViewedQuery[0]->viewed) ? $totalViewedQuery[0]->viewed : 0;

        $expiredBoardStats->total_unique_viewed = BoardTracking::where('board_id', $boardID)->where('viewed', '<>', 0)->distinct('row_id')->count('row_id');

        $failedQuery = DB::select("SELECT count(*) as failed from board_tracking where board_id =" . $boardID . " AND status = 'failed'");
        $expiredBoardStats->total_failed = !empty($failedQuery[0]->failed) ? $failedQuery[0]->failed : 0;

        $totalAndroidSentQuery = DB::select("SELECT count(*) as total from board_tracking where board_id =" . $boardID . " AND device_type='android' AND sent <> 0");
        $expiredBoardStats->total_android_sent = !empty($totalAndroidSentQuery[0]->total) ? $totalAndroidSentQuery[0]->total : 0;

        $totalAndroidViewedQuery = DB::select("SELECT count(*) as total from board_tracking where board_id =" . $boardID . " AND device_type='android' AND viewed <> 0");
        $expiredBoardStats->total_android_viewed = !empty($totalAndroidViewedQuery[0]->total) ? $totalAndroidViewedQuery[0]->total : 0;

        $totalAndroidFailedQuery = DB::select("SELECT count(*) as total from board_tracking where board_id =" . $boardID . " AND device_type='android' AND status = 'failed'");
        $expiredBoardStats->total_android_failed = !empty($totalAndroidFailedQuery[0]->total) ? $totalAndroidFailedQuery[0]->total : 0;

        $totalIOSSentQuery = DB::select("SELECT count(*) as total from board_tracking where board_id =" . $boardID . " AND device_type='ios' AND sent <> 0");
        $expiredBoardStats->total_ios_sent = !empty($totalIOSSentQuery[0]->total) ? $totalIOSSentQuery[0]->total : 0;

        $totalIOSViewedQuery = DB::select("SELECT count(*) as total from board_tracking where board_id =" . $boardID . " AND device_type='ios' AND viewed <> 0");
        $expiredBoardStats->total_ios_viewed = !empty($totalIOSViewedQuery[0]->total) ? $totalIOSViewedQuery[0]->total : 0;

        $totalIOSFailedQuery = DB::select("SELECT count(*) as total from board_tracking where board_id =" . $boardID . " AND device_type='ios' AND status = 'failed'");
        $expiredBoardStats->total_ios_failed = !empty($totalIOSFailedQuery[0]->total) ? $totalIOSFailedQuery[0]->total : 0;

        $expiredBoardStats->targeted_users = \App\Concerns\exportUsers::exportUsers($boardID, 'board', $appGroupID, true);

        $lastTenRowIDsQuery = DB::select("SELECT row_id FROM board_tracking where board_id=" . $boardID . " AND viewed > 0 ORDER BY viewed_at DESC limit 10");
        $lastTenRowIDs = [];
        foreach ($lastTenRowIDsQuery as $item) {
            array_push($lastTenRowIDs, $item->row_id);
        }
        $expiredBoardStats->last_ten_row_ids = !empty($lastTenRowIDs) ? json_encode($lastTenRowIDs) : NULL;

        $expiredBoardStats->save();

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
