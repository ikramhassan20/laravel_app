<?php

namespace App\Console\Commands;

use App\CampaignQueue;
use App\Traits\CommonTrait;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;

class CampaignQueueUsersCommand extends Command
{
    use CommonTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backend:campaign:queue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to dispatch campaign queue for admin backend';

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
        $id = $this->argument('id');

        $queue = CampaignQueue::find($id);

        if ($queue->status == CampaignQueue::STATUS_AVAILABLE) {
            self::campaignQueueDispatch($queue);
        }
    }

    protected function configure()
    {
        $this->addArgument('id', InputArgument::REQUIRED, 'Queue ID');
    }
}
