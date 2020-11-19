<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;


class exportUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:users {params*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export users for segment and campaign';

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
        \App\Concerns\exportUsers::exportUsers((int)$this->argument("params")[0], $this->argument("params")[1], (int)$this->argument("params")[2]);

    }
}
