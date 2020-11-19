<?php

namespace App\Console\Commands;

use App\CampaignQueue;
use App\Components\AppStatusMessages;
use App\Components\SQL_SP_VW_Identifier;
use Illuminate\Console\Command;
use App\Components\CampaignDispatcher;

class AddItemsToCampaignQueuesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaign:create_queues';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add campaigns for dispatch to campaign_queues table';

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
        $view_sql = "SELECT * FROM " . SQL_SP_VW_Identifier::VIEW_CAMPAIGN_QUEUES;
        $this->info($view_sql);

        $results = \DB::select($view_sql);

        if (!empty($results)) {
            foreach ($results as $result) {

                $_queue = CampaignQueue::where('campaign_id', '=', $result->id)->first();
                if(!$_queue){
                    $queue = new CampaignQueue();
                    $queue->campaign_id = $result->id;
                    $queue->status = CampaignQueue::STATUS_AVAILABLE;
                    $queue->priority = CampaignQueue::priority(isset($result->campaign_priority) ? $result->campaign_priority : 'medium');
                    $queue->start_at = isset($result->start_at) ? $result->start_at : null;
                    $queue->details = isset($result->details) ? $result->details : '';
                    $queue->save();
                }
            }
            $this->info(AppStatusMessages::ADD_ITEMS_QUEUE_SUCCESS);
        } else {
            $this->error(AppStatusMessages::UNABLE_TO_ADD_ITEMS_QUEUE);
        }
    }

}
