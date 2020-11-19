<?php

namespace App\Jobs;

use App\CampaignQueue;
use App\Components\CampaignQueueComponent;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class DispatchCampaignQueueJob extends Job implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $queueItem;

    /**
     * Create a new job instance.
     *
     * @param int $queueItem
     *
     * @return void
     */
    public function __construct($queueItem)
    {
        $this->queueItem = $queueItem;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        try {
            dd('here');
            $target = new CampaignQueueComponent($this->queueItem);
            dd($target->process());
        } catch (\Exception $exception) {
            $this->failed($exception);
        }
    }

    /**
     * Update fields when job fails.
     *
     * @param \Exception $exception
     *
     * @return string
     */
    public function failed(\Exception $exception)
    {
        dd($exception);
//        $this->queueItem->status = CampaignQueue::STATUS_FAILED;
//        $this->queueItem->error_message = $exception->getMessage();
//        $this->queueItem->save();
    }
}
