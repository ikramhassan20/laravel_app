<?php

namespace App\Console\Commands;

use App\Apps;
use App\AppUsers;
use Illuminate\Console\Command;
use App\AppUserTokens;
use App\Cache\CacheKeys;
use Illuminate\Support\Facades\DB;
use Log;

class RefreshRevokedTokens extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'revoked-tokens:refresh {--old_token=} {--new_token=} {--new_device_type=} {--exclude_revoked}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command used to refresh revoked token';

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
            echo "Error While processing command: " . $exception->getMessage() . "\n";
        }

        // call terminate execution
        $this->terminate();
    }


    protected function process()
    {
        try {
            $oldToken = $this->option('old_token');
            $newToken = $this->option('new_token');
            $newDeviceType = $this->option('new_device_type');
            $excludeRevoked = $this->option('exclude_revoked');

            if (empty($oldToken) || empty($newToken) || empty($newDeviceType)) {

                throw new \Exception("Missing input data, please provide old_token, new_token and new_device_type values");
            }

            // get old token from db, and update new token on db and cache
            if ($excludeRevoked) {
                $query = AppUserTokens::withTrashed()->where('device_token', $oldToken);
            } else {
                $query = AppUserTokens::withTrashed()->where('device_token', $oldToken)->where('is_revoked', 1);
            }

            $chunkSize = config('engagement.api.export.chunk_size');
            $count = 0;
            DB::beginTransaction();
            $query->chunk($chunkSize, function ($tokens) use ($newToken, $newDeviceType, $count) {

                foreach ($tokens as $token) {
                    $count++;

                    $app_group_id = $token->user->app_group_id;
                    $row_id = $token->row_id;

                    $app = Apps::select(['name', 'app_id'])
                        ->where('app_group_id', $app_group_id)
                        ->where('platform', $newDeviceType)
                        ->where('is_active', 1)
                        ->first();
                    if (empty($app)) {
                        echo 'No ' . $newDeviceType . ' App found' . PHP_EOL;
                        exit;
                    }

                    // Updating app_id in app_users table
                    AppUsers::where('row_id', $row_id)->update(['app_id' => $app->app_id]);

                    $token->is_revoked = 0;
                    $token->status = 1;
                    $token->deleted_at = NULL;
                    $token->device_token = $newToken;
                    $token->device_type = $newDeviceType;
                    $token->app_id = $app->app_id;
                    $token->app_name = $app->name;
                    $token->save();

                    $_key = new CacheKeys($app_group_id);
                    $cache_key = $_key->generateAppUserLoginSignupKey($row_id);
                    $tokenCache = \Cache::get($cache_key);
                    if (!empty($tokenCache)) {

                        $tokenData = json_decode($tokenCache, true);
                        $tokenData[0]['app_id'] = $app->app_id;
                        $tokenData[0]['apps_users_tokens']['device_token'] = $newToken;
                        $tokenData[0]['apps_users_tokens']['device_type'] = $newDeviceType;
                        $tokenData[0]['apps_users_tokens']['app_name'] = $app->name;
                        $tokenData[0]['apps_users_tokens']['app_id'] = $app->app_id;
                        $tokenData[0]['apps_users_tokens']['revoked'] = 0;
                        $tokenData[0]['apps_users_tokens']['status'] = 1;
                        $tokenData[0]['apps_users_tokens']['deleted_at'] = NULL;

                        \Cache::forget($cache_key);

                        // put new data in cache
                        \Cache::forever($cache_key, \GuzzleHttp\json_encode($tokenData));
                    }


                    if ($count % 1000 == 0) {
                        echo ".";
                    }
                }
            });
            DB::commit();

            echo "All Done" . PHP_EOL;

        } catch (\Exception $exception) {
            DB::rollBack();
            throw new \Exception($exception->getMessage());
        }

    }
}
