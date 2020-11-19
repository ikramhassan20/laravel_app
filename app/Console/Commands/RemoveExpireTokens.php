<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Laravel\Passport\Passport;
use Carbon\Carbon;

class RemoveExpireTokens extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'expire-tokens:remove';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'remove expire tokens';

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
        try{
            Passport::token()
                ->where('revoked', 0)
                ->where('expires_at', '<', Carbon::now())
                ->delete();

            echo 'Removed expired tokens.' . "\n";
        }
        catch (\Exception $exception){
            Log::info("Unable to remove expired tokens: ". $exception->getMessage());
        }

        // call terminate execution
        $this->terminate();

    } // end of function
}