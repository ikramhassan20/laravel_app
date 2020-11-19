<?php

namespace App\Console\Commands;

use App\Cache\CampaignSegmentCache;
use App\Campaign;
use Illuminate\Console\Command;

class UpdateCampaignSegmentCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:update_campaign_segment_cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates campaign segment cache from database';

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
        $campaigns = Campaign::all();
//        dd($campaigns);
        foreach ($campaigns as $campaign) {
            $obj = new CampaignSegmentCache();
            $flag = $obj->saveCampaignSegmentCache($campaign);
            if ($flag) {
                $this->info('Campaign Segment Cache has been Updated');
            } else {
                $this->error('Unable to Update Campaign Segment Cache');
            }
        }
    }
}
