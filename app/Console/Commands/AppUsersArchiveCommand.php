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

class AppUsersArchiveCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app_users:archive:range';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove app users from database and clear cache.';

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

            /*$company_id = '29';
            $app_group_id = '60';
            $start = '500000';
            $end = '549999';
            $limit = '200';

            $app_users = AppUsers::where('user_id', '>=', $start)
                ->where('user_id', '<=', $end)
                ->where('company_id', '=', $company_id)
                ->where('app_group_id', '=', $app_group_id)
                ->get(['app_group_id', 'row_id', 'user_id'])->chunk($limit);

            if (count($app_users) > 0) {
                foreach ($app_users as $users) {
                    foreach ($users as $app_user) {

                        // generate cache key
                        $_key = new CacheKeys($app_user->app_group_id);
                        $cache_key = $_key->generateAppUserLoginSignupKey($app_user->row_id);
                        //dump($cache_key, $app_user->app_group_id, $app_user->row_id);

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
                dd("Success: All users in rage from ". $start." end: ". $end ." archived successfully.");
            }
            else{
                dd("Sorry, now user found in this range.");
            }*/
        }
        catch (\Exception $exception){

            // log the exception
            Log::error($exception->getMessage());

            // return the response
            dd($exception->getMessage());
        }
    }
}
