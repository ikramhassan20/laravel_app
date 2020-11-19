<?php

namespace App\Console\Commands;

use App\AppGroup;
use App\AppUserActivity;
use App\AppUserTokens;
use App\AttributeData;
use App\CampaignTracking;
use App\LinkTrackings;
use App\NewsFeedImpression;
use App\User;
use App\AppUsers;
use App\Cache\CacheKeys;
use App\Cache\AppUserLoginSignupCache;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AppUsersCacheCleanCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app_users:cache:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove is_deleted app users from database and clear cache.';

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

            // generate cache for dashboard stats
            $app_users = AppUsers::where('is_deleted', '1')->get();

            if (count($app_users) > 0) {
                foreach ($app_users as $app_user) {

                    // prepare and parse app group id
                    $app_group_id = $app_user->app_group_id;

                    // generate cache key
                    $_key = new CacheKeys($app_group_id);
                    $cache_key = $_key->generateAppUserLoginSignupKey($app_user->row_id);

                    // removing cache contents
                    AppUserLoginSignupCache::removeEntry($cache_key);

                    // removing app users from the database
                    AttributeData::where('row_id', $app_user->row_id)->delete();
                    AppUserActivity::where('row_id', $app_user->row_id)->delete();
                    AppUserTokens::where('row_id', $app_user->row_id)->forcedelete();
                    CampaignTracking::where('row_id', $app_user->row_id)->delete();
                    LinkTrackings::where('row_id', $app_user->row_id)->forcedelete();
                    NewsFeedImpression::where('row_id', $app_user->row_id)->forcedelete();
                    AppUsers::where('row_id', $app_user->row_id)->forcedelete();
                }
            }
        }
        catch (\Exception $exception){

            // log the exception
            Log::error($exception->getMessage());

            // return the response
            return $exception->getMessage();
        }

        // call terminate execution
        $this->terminate();
    }
}
