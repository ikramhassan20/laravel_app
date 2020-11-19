<?php

namespace App\Listeners;

use App\Events\AddSegmentCacheEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Cache\AppGroupSegmentCache;

class AddSegmentCacheEventListener
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
     * @param  AddSegmentCacheEvent  $event
     * @return void
     */
    public function handle(AddSegmentCacheEvent $event)
    {
        $segment_cache = new AppGroupSegmentCache();
        $segment_cache->saveAppGroupSegmentCache($event->segment);

        $status = $segment_cache->saveAppGroupSegmentRowsCache($event->segment, true);

        if ($status === false) {
            $segment_cache->saveAppGroupSegmentRowsCache($event->segment);
        }
    }
}
