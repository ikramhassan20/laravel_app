<?php

namespace App\Console\Commands;

use App\BoardQueue;
use App\Traits\CommonTrait;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;

class BoardQueueUsersCommand extends Command
{

    use CommonTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backend:board:queue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to dispatch board queue for admin backend';

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
        $queue = BoardQueue::find($id);

        if ($queue->status == BoardQueue::STATUS_AVAILABLE) {
            $queue->status = BoardQueue::STATUS_PROCESSING;
            $queue->save();
            self::boardQueueDispatch($queue);
        }
    }

    protected function configure()
    {
        $this->addArgument('id', InputArgument::REQUIRED, 'Queue ID');
    }

}