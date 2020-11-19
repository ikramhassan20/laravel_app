<?php

namespace App\Console\Commands;

use App\BoardQueue;
use App\Http\Resources\V1\Notifications\SendNotifications;
use App\Jobs\DispatchCampaignQueueJob;
use App\Jobs\PushJobWorker;
use App\Jobs\TestJobNew;
use App\Components\CampaignDispatcher;
use App\Traits\CommonTrait;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Queue;
use App\Board;

class DispatchBoardQueuesCommand extends BaseCommand
{
    use CommonTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'board:dispatch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch Board Queues';

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

        //$startTime = Carbon::now()->startOfMinute();
        //$endTime = Carbon::now()->endOfMinute();
        $currentTime = Carbon::now()->format('Y-m-d H:i:s');

        // getting available jobs from queue
        $queuesList = BoardQueue::where('status', '=', BoardQueue::STATUS_AVAILABLE)
            //->whereBetween('start_at', [$startTime, $endTime])
            ->where('start_at', '<=', $currentTime)
            ->orderBy('priority', 'ASC')
            ->get();

           if(count($queuesList) > 0){
                // loop through each campaigns
                foreach ($queuesList as $queue) {
                    Log::info('Board Queue: ' . \GuzzleHttp\json_encode($queue));
                    $startTime = microtime(true);

                    // updating campaign queue status to completed.
                    $queue->status = BoardQueue::STATUS_PROCESSING;
                    $queue->save();
                    self::boardQueueDispatch($queue);

                    $endTime = microtime(true);
                    $responseTime = ($endTime - $startTime)*1000;
                    $this->logResponse($responseTime, $queue->board_id, \App\Helpers\CommonHelper::getCompanyIdByBoardID($queue->board_id), 'board');
                }
            }
            else{
                Log::emergency('No board found.');
                $this->error("No board found for dispatch.");
            }

        // call terminate execution
        //$this->terminate();

    } // end of function

} // end of class
