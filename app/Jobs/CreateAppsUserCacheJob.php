<?php

namespace App\Jobs;

use App\Http\Resources\V1\Users\App\SyncRowDataCache;
use App\Http\Resources\V1\Users\App\SyncRowTokensCache;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateAppsUserCacheJob implements ShouldQueue
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
        $data = $this->model->toArray();
        $data['tokens'] = $this->model->tokens->toArray();

        $dataCache = new SyncRowDataCache($this->model->company_id, $data);
        $dataCache->save($data);
    }
}
