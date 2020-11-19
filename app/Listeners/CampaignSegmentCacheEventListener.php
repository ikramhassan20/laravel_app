<?php

namespace App\Listeners;

use App\Events\CampaignSegmentCacheEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Cache\CampaignSegmentCache;

class CampaignSegmentCacheEventListener
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
     * @param  CampaignSegmentCacheEvent  $event
     * @return void
     */
    public function handle(CampaignSegmentCacheEvent $event)
    {
        $campaign_segment_cache = new CampaignSegmentCache();
        $campaign_segment_cache->saveCampaignSegmentCache($event->campaign);
    }
}
