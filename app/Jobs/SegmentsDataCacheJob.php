<?php

namespace App\Jobs;

use App\Cache\AppGroupSegmentCache;
use App\Cache\CacheKeys;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SegmentsDataCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $segment;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($segment)
    {
        $this->segment = $segment;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $segment = $this->segment;

        //$_key = new CacheKeys($segment->app_group_id);
        //$cache_key = $_key->generateAppGroupSegmentRowsKey($segment->id);
        //\Log::info('Segment key: '.$cache_key);
        //AppGroupSegmentCache::removeEntry($cache_key);

        $cache = new AppGroupSegmentCache();
        $cache->saveAppGroupSegmentRowsCache($segment, true);
    }
}
