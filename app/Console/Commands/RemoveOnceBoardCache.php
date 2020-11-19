<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Board;
use App\Cache\CacheKeys;
use App\Cache\OnceBoardRowIdsCache as OnceBoradCache;
use Carbon\Carbon;


/**
 * This Class is used to remove Once board cache
 * Class RemoveOnceBoardCache
 * @package App\Console\Commands
 */
class RemoveOnceBoardCache extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'once-board-cache:remove';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'remove once board cache';

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


    /**
     * Funcrion used to process command
     * @throws \Exception
     */
    protected function process()
    {
        try{
            // get boards from db
            $boards = Board::where('end_time', '>', Carbon::now()->subDays(1))
                ->where(function($query){
                    $query->where('end_time', '<', Carbon::now())
                        ->orWhereIn('status', ['suspended', 'expired']);
                })
                ->select('id', 'app_group_id')
                ->get()
                ->toArray();

            if(count($boards) > 0){
                foreach($boards as $board){

                    $board_id = $board['id'];
                    $app_group_id = $board['app_group_id'];

                    // get cache key
                    $_key = new CacheKeys($app_group_id);
                    $cache_key = $_key->generateOnceTypeBoardRowIdsCacheKey($board_id);
                    $boardUnionCacheKey = $_key->generateBoardSegmentsUnionCacheKey($board_id);

                    try{
                        // try to remove Once board cache
                        OnceBoradCache::removeEntry($cache_key);
                        OnceBoradCache::removeEntry($boardUnionCacheKey);
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
}
