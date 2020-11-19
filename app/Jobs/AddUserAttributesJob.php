<?php

namespace App\Jobs;

use App\Http\Resources\V1\Users\App\SyncUserData;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class AddUserAttributesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var int
     */
    protected $company_id;

    /**
     * @var array
     */
    protected $params;

    /**
     * Create a new job instance.
     *
     * @param int   $company_id
     * @param array $params
     *
     * @return void
     */
    public function __construct($company_id, $params)
    {
        $this->company_id = $company_id;
        $this->params = $params;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        (new SyncUserData($this->company_id))->addInDB($this->params);
    }
}
