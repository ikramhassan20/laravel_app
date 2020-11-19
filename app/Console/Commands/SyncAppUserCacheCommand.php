<?php

namespace App\Console\Commands;

use App\AppUsers;
use App\Cache\AppUserLoginSignupCache;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncAppUserCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app_users:cache:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to sync App Users Cache with DB';

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
        $chunkSize = config('engagement.api.export.chunk_size');
        $app_users = AppUsers::select(['row_id', 'user_id', 'app_id', 'app_group_id', 'company_id'])
            ->where('is_deleted', 0)
            ->where('deleted_at', NULL)
            ->chunk($chunkSize, function ($appUsers) {
                foreach ($appUsers as $user) {
                    echo 'Syncing cache for row_id:'. $user->row_id . PHP_EOL;
                    $cache = new AppUserLoginSignupCache();
                    $cache->saveAppUserSignupCache([
                        'user_id' => $user->user_id,
                        'app_id' => $user->app_id,
                        'company_id' => $user->company_id,
                        'app_group_id' => $user->app_group_id,
                        'mode' => AppUsers::USER_REVOKED
                    ]);
                }
            });

        echo 'All Done';
    }
}
