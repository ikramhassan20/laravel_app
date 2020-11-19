<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Cache\CacheKeys;
use Illuminate\Support\Facades\Redis;
use App\AppUserTokens;
use Carbon\Carbon;
use Log;
use DB;

class ProcessRevokedTokens extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'revoked-tokens:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command used to process revoked tokens from temp table';

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
            $this->process();
        } catch (\Exception $exception) {
            Log::info("Error While processing command: " . $exception->getMessage());
            echo "Error While processing command: " . $exception->getMessage();
        }

        // call terminate execution
        $this->terminate();
    }


    protected function process()
    {
        try {
            $chunkSize = config('engagement.api.export.chunk_size');

            AppUserTokens::withTrashed()
                ->where('is_revoked', 1)
                ->where('is_cache_sync', 0)
                ->chunk($chunkSize, function ($appUserTokens) {

                    foreach ($appUserTokens as $appUserToken) {

                        $app_group_id = $appUserToken->user->app_group_id;
                        $row_id = $appUserToken->row_id;

                        $_key = new CacheKeys($app_group_id);
                        $cache_key = $_key->generateAppUserLoginSignupKey($row_id);
                        $tokenCache = \Cache::get($cache_key);
                        if (!empty($tokenCache)) {

                            $tokenData = json_decode($tokenCache, true);
                            $tokenData[0]['apps_users_tokens']['revoked'] = 1;
                            $tokenData[0]['apps_users_tokens']['status'] = 0;
                            $tokenData[0]['apps_users_tokens']['is_cache_sync'] = 1;
                            $tokenData[0]['apps_users_tokens']['deleted_at'] = date('Y-m-d H:i:s');

                            \Cache::forget($cache_key);

                            // put new data in cache
                            \Cache::forever($cache_key, \GuzzleHttp\json_encode($tokenData));
                        }

                        $appUserToken->is_cache_sync = 1;
                        $appUserToken->save();
                    }
                });

        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }

    }

}
