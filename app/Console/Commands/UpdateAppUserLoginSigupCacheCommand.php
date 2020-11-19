<?php

namespace App\Console\Commands;

use App\Apps;
use App\AppUsers;
use App\Cache\AppUserLoginSignupCache;
use Illuminate\Console\Command;

class UpdateAppUserLoginSigupCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:update_app_user_login_signup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command will update App User Login and Sign up Cache from Database ';

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
        $appUsers = AppUsers::all();
        foreach ($appUsers as $user) {
            $params = [
                'company_id' => $user->company_id,
                'app_id' => $user->app_id,
                'user_id' => $user->user_id
            ];
            $obj = new AppUserLoginSignupCache();
            $flag = $obj->saveAppUserLoginCache($params);
            if ($flag) {
                $this->info('App User cache Login Signup Updated');
            } else {
                $this->error('Failed to Update App User Login Signup cache');
            }
        }

    }
}
