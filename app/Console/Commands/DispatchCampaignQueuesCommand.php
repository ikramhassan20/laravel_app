<?php

namespace App\Console\Commands;

use App\CampaignQueue;
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

class DispatchCampaignQueuesCommand extends BaseCommand
{
    use CommonTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaign:campaign:dispatch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch campaign queues.';

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
        $queuesList = CampaignQueue::where('status', '=', CampaignQueue::STATUS_AVAILABLE)
            //->whereBetween('start_at', [$startTime, $endTime])
            ->where('start_at', '<=', $currentTime)
            ->orderBy('priority', 'ASC')
            ->get();

        if (count($queuesList) > 0) {

            // loop through each campaigns
            foreach ($queuesList as $queue) {
                Log::info('Queue: ' . \GuzzleHttp\json_encode($queue));
                $startTime = microtime(true);

                // updating campaign queue status to completed.
                $queue->status = CampaignQueue::STATUS_PROCESSING;
                $queue->save();

                self::campaignQueueDispatch($queue);

                $endTime = microtime(true);
                $responseTime = ($endTime - $startTime)*1000;
                $this->logResponse($responseTime, $queue->campaign_id, \App\Helpers\CommonHelper::getCompanyIdByCampaignID($queue->campaign_id), 'campaign');
            }

        } else {
            Log::emergency('No campaign found.');
            $this->error("No campaigns found for dispatch.");
        }

        // call terminate execution
        //$this->terminate();
    }
}
