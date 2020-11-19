<?php

namespace App\Console\Commands;

use App\Cache\AppGroupSegmentCache;
use App\Segment;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\SoftDeletes;

class UpdateAppGroupSegmentCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:update_app_group_segment_cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command updates the all the cache related to segments';

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
        $segments = Segment::all();
        dd($segments);
        foreach ($segments as $segment) {
            $obj = new AppGroupSegmentCache();
            $appGroupSegmentCache = $obj->saveAppGroupSegmentCache($segment);
            if ($appGroupSegmentCache) {
                $this->info('App Group Segment cache Updated');
            } else {
                $this->error('Failed to Update App Group Segment cache');
            }
            $appGroupSegmentCacheRows = $obj->saveAppGroupSegmentRowsCache($segment, true);
            if ($appGroupSegmentCacheRows) {
                $this->info('App Group Segment Rows cache Updated');
            } else {
                $this->error('Failed to Update App Group Segment Rows cache');
            }

        }

    }
}
