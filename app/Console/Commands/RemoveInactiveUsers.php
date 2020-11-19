<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\AppUsers;
use App\Cache\CacheKeys;
use Illuminate\Support\Facades\Redis;
use Log;

class RemoveInactiveUsers extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inactive-users:remove';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command used to remove inactive users from cache';

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
        try{
            $this->process();
        }
        catch(\Exception $exception){
            Log::info("Error While processing command: ". $exception->getMessage());
            echo "Error While processing command: ". $exception->getMessage();
        }

        // call terminate execution
        $this->terminate();
    }

    protected function process()
    {
        try {

            $daysToConsiderUserInactive = (!empty(config('engagement.days_to_consider_user_inactive')) ? config('engagement.days_to_consider_user_inactive') : 30);
            $users = AppUsers::whereRaw('DATE_ADD(last_login, INTERVAL '.$daysToConsiderUserInactive.' DAY) < UTC_TIMESTAMP()')
                ->cursor();

            foreach($users as $user){

                $rowId = $user->row_id;
                $appGroupId = $user->app_group_id;
                $cache_prefix =  config('cache.prefix');

                $_key = new CacheKeys($appGroupId);
                $userCacheKey = $cache_prefix . ":" .$_key->generateAppUserLoginSignupKey($rowId);
                Redis::del($userCacheKey);

            }

        }
        catch (\Exception $exception){
            throw new \Exception($exception->getMessage());
        }
    }
}
