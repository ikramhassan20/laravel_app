<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Campaign;
use App\Cache\CacheKeys;
use Carbon\Carbon;
use Log;

class RemoveCampaignUnionCache extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaign-union-cache:remove';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'remove campaign union cache';

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
        }

        // call terminate execution
        $this->terminate();
    }

    protected function process()
    {
        try{
            // get campaigns from db
            $campaigns = Campaign::without('variants', 'variants.translations', 'segments', 'actions', 'schedules')
                ->where('end_time', '>', Carbon::now()->subDays(1))
                ->where(function($query){
                    $query->where('end_time', '<', Carbon::now())
                        ->orWhereIn('status', ['suspended', 'expired']);
                })
                ->select('id', 'app_group_id')
                ->get()
                ->toArray();

            $OnceExpireCampaigns = Campaign::without('variants', 'variants.translations', 'segments', 'actions', 'schedules')
                ->join('campaign_queue', 'campaign.id', '=', 'campaign_queue.campaign_id')
                ->where('campaign.schedule_type', '=', 'once')
                ->where('campaign.delivery_type', '=', 'schedule')
                ->where(function($query){
                    $query->where('campaign_queue.status', '=', 'Complete')
                        ->orWhereIn('campaign.status', ['suspended', 'expired']);
                })
                ->select('campaign.id', 'campaign.app_group_id')
                ->get()
                ->toArray();

            $expireCampaigns = array_merge($campaigns, $OnceExpireCampaigns);
            //print_r($expireCampaigns);

            if(count($expireCampaigns) > 0){
                foreach($expireCampaigns as $campaign){

                    $campaign_id = $campaign['id'];
                    $app_group_id = $campaign['app_group_id'];

                    // get cache key
                    $_key = new CacheKeys($app_group_id);
                    $cache_key = $_key->generateCampaignSegmentsUnionCacheKey($campaign_id);

                    try{
                        // try to remove campaign union cache
                        static::removeEntry($cache_key);
                    }
                    catch(\Exception $e){
                        throw new \Exception("Unable to remove cache key = ". $cache_key ." ".$e->getMessage());
                    }

                }
            }
        }
        catch (\Exception $exception){
            throw new \Exception($exception->getMessage());
        }

    }

    protected static function removeEntry($cache_key)
    {
        if (!in_array(config('cache.default'), ['array', 'database', 'file', 'redis'])) {
            \Artisan::call('cache:clear', [
                '--tags' => $cache_key
            ]);
        }

        \Cache::forget($cache_key);
    }
}
