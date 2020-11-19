<?php

namespace App\Jobs;

use App\AppUsers;
use App\AppUserTokens;
use App\Http\Resources\V1\Segments\SyncAppGroupSegmentsCache;
use App\Http\Resources\V1\Segments\SyncSegmentUserAttributeCache;
use App\Segment;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateCompanySegmentsCacheJob implements ShouldQueue
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
        $segments = $this->model->segments;
        $segmentIds = $segments->pluck('id')->unique()->toArray();

        $itemCache = new SyncAppGroupSegmentsCache($this->model->company_id, [
            'app_group_id'  => $this->model->id,
        ]);
        $itemCache->setItemData($segmentIds);

        foreach ($segments as $segment) {
            if ($segment->type === Segment::SEGMENT_TYPE_USER) {
                $users = AppUsers::select('row_id')
                    ->whereRaw($segment->criteria)
                    ->get()->toArray();
                $tokens = AppUserTokens::select('row_id')
                    ->whereRaw($segment->criteria)
                    ->get()->toArray();
                $items = collect($users)->merge($tokens)
                    ->unique('row_id')->toArray();
            }

            $itemCache = new SyncSegmentUserAttributeCache($this->model->company_id, [
                'app_group_id'  => $this->model->id,
                'segment_id'    => $segment->id
            ]);
            $itemCache->setItemData($items);
        }
    }
}
