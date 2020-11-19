<?php

namespace App\Console\Commands;

use App\AppGroup;
use App\Cache\AppUserLoginSignupCache;
use App\Cache\PopularAppsCache;
use App\User;
use Illuminate\Console\Command;
use App\Cache\CampaignStatsCache;
use App\Cache\CampaignConversionStatsCache;
use App\Cache\NewsfeedStatsCache;
use Illuminate\Support\Facades\Log;

class DashboardStatsCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dashboard:stats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'create cache for dashboard stats';

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

            // generate popular apps cache
            //PopularAppsCache::generatePopularAppsCache();

            // generate cache for dashboard stats
            $company = User::where('is_active', '1')->where('deleted_at', Null)->get();
            if ($company) {
                foreach ($company as $_company) {

                    // company id
                    $company_id = $_company->id;

                    // list app groups for present company
                    $app_groups = AppGroup::where('company_id', $company_id)->get();
                    if ($app_groups) {
                        foreach ($app_groups as $_app_group) {

                            $app_group_id = $_app_group->id;

                            // generate app user cache
                            $app_users = new AppUserLoginSignupCache();
                            $app_users->generateAppUserStats($app_group_id, $company_id);

                            // generate campaign stats cache
                            CampaignStatsCache::generateCampaignStatsCache($app_group_id);

                            // generate campaign conversion cache
                            CampaignConversionStatsCache::generateCampaignConversionStatsCache($app_group_id);

                            // generate newsfeed views cache
                            NewsfeedStatsCache::generateNewsfeedViewsStatsCache($app_group_id);

                            // generate newsfeed click cache
                            NewsfeedStatsCache::generateNewsfeedClicksStatsCache($app_group_id);

                            // generate popular apps cache
                            PopularAppsCache::generatePopularAppsCache($app_group_id);
                        }
                    }

                }
            }
        }
        catch (\Exception $exception){

            Log::error($exception->getMessage());

            return $exception->getMessage();
        }
    }
}
