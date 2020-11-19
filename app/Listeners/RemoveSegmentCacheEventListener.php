<?php

namespace App\Listeners;

use App\Events\AddSegmentCacheEvent;
use App\Events\RemoveSegmentCacheEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Cache\AppGroupSegmentCache;

class RemoveSegmentCacheEventListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  RemoveSegmentCacheEvent  $event
     * @return void
     */
    public function handle(RemoveSegmentCacheEvent $event)
    {
        $segment_cache = new AppGroupSegmentCache();
        $segment_cache->deleteFromAppGroupSegmentRowsCache($event->segment);

        $segment_cache->deleteFromAppGroupSegmentCache($event->segment);
    }
}
