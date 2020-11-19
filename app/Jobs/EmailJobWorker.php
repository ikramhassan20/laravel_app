<?php

namespace App\Jobs;

use App\BoardVariant;
use App\Campaign;
use App\CampaignQueue;
use App\Components\BoardEmailQueueComponents;
use App\Components\CampaignEmailQueueComponents;
use App\Components\CampaignQueueComponent;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class EmailJobWorker implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    /**
     * @Document Payload recived for Push
     */
    private $payload;
    /**
     * EmailJobWorker constructor.
     * @param $payload
     */
    public function __construct($payload)
    {
        $this->payload = $payload;

    }
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info('Job started: ' . Carbon::now()->format('Y-m-d h:i:s'));
        $data = (isset($this->payload[0])) ? $this->payload[0] : $this->payload;

        $is_board = (isset($data['data']['is_board'])) ? (bool)$data['data']['is_board'] : false;
        if($is_board !== false){
            $campaign_type = strtolower(BoardVariant::VARIANT_EMAIL_CODE);
            $board_id = (isset($data['data']['id'])) ? $data['data']['id'] : "";

            if ( strtolower($data['data']['type']) == strtolower(BoardVariant::VARIANT_EMAIL_CODE) ) {
                $target = new BoardEmailQueueComponents($data);
                $result=$target->process();
                Log::info('Job completed: ' . Carbon::now()->format('Y-m-d h:i:s'));
            }else{
                Log::info('Job failed: ' . Carbon::now()->format('Y-m-d h:i:s'));
                throw new \Exception("Failed, Board type is not email.");
            }
        }
        else{
            $campaign_type = Campaign::CAMPAIGN_EMAIL_CODE;
            $campaign_id = (isset($data['data']['id'])) ? $data['data']['id'] : "";

            if (strtolower($data['data']['type']) == strtolower(Campaign::CAMPAIGN_EMAIL_CODE)) {
                $target = new CampaignEmailQueueComponents($data);
                $result=$target->process();
                Log::info('Job completed: ' . Carbon::now()->format('Y-m-d h:i:s'));
            }else{
                Log::info('Job failed: ' . Carbon::now()->format('Y-m-d h:i:s'));
                throw new \Exception("Failed, campaign type is not email.");
            }
        }
    }
}
