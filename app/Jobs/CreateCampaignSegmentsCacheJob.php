<?php

namespace App\Jobs;

use App\Http\Resources\V1\Campaigns\SyncCampaignSegmentsCache;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateCampaignSegmentsCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $model;

    /**
     * Create a new job instance.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return void
     */
    public function __construct(\Illuminate\Database\Eloquent\Model $model)
    {
        $this->model = $model;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $campaigns = $this->model->campaigns;
        if ($campaigns->count() > 0) {
            foreach ($campaigns as $campaign) {
                $segments = $campaign->segments->pluck('id')->unique()->toArray();

                $itemCache = new SyncCampaignSegmentsCache($this->model->company_id, [
                    'app_group_id'  => $this->model->id,
                    'campaign_id'   => $campaign->id
                ]);
                $itemCache->setItemData($segments);
            }
        }
    }
}
