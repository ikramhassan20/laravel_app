<?php

namespace App\Console\Commands;

use App\AppGroup;
use App\Cache\AppGroupSegmentCache;
use App\Cache\CacheKeys;
use App\Jobs\SegmentsDataCacheJob;
use App\User;
use Illuminate\Console\Command;

class SegmentsDataCacheCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'segment:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to generate segments cache';

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
        \Log::info('Segment cache command: segment:cache');
        $companies = User::with('roles')->whereHas('roles', function ($q) {
            $q->where('name', 'COMPANY');
        })->get();

        foreach ($companies as $company) {
            \Log::info("company ID: " . $company->id);
            $groups = AppGroup::with('segments', 'segments.campaigns')
                ->where('company_id', $company->id)
                ->get();

            if (count($groups) > 0) {
                foreach ($groups as $group) {
                    $segments = $group->segments;

                    if (count($segments) > 0) {
                        foreach ($segments as $segment) {
                            // app_group_id_1_segment_1_rows
                            SegmentsDataCacheJob::dispatch($segment)
                                ->onQueue('segmentcache')
                                ->delay(now()->addMinutes(1));
                        }
                    }
                }
            }
        }

        // call terminate execution
        $this->terminate();
    }
}
