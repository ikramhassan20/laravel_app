<?php

namespace App\Console\Commands;

use App\Campaign;
use App\CampaignQueue;
use App\Jobs\TestJobNew;
use App\Events\CampaignSegmentCacheEvent;
use App\Cache\CampaignSegmentCache;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Queue;

class CampaignSegmentCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaign:segment';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Campaign segment command.';

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
            $campaign_id = 17;
            $campaign = Campaign::find($campaign_id);

            $campaign_segment_cache = new CampaignSegmentCache();
            $campaign_segment_cache->saveCampaignSegmentCache($campaign);

        } catch (\Exception $exception) {
            dd($exception);
        }
    }
}
