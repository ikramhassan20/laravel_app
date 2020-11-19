<?php

namespace App\Console\Commands;

use App\Components\RateLimitingComponents;
use Illuminate\Console\Command;

class RateLimitingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Rate:Limiting';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rate Limiting Description';

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
        //
        $payload =
            [
                [
                    "row_id" => 1000,
                    "campaign_id" => 7,
                    "user_id"=>2,
                    "company_id" => 50,
                    "firebase_token" => 'ABC123',
                    "device_token" => '987654',
                    "track_id" => 789,
                    "server_firebase_key" => 'SERVER85417ASDABC123',
                    "message_type" => "push",
                    "campaign_content" => "content of campaign text/html",
                    "email_subject" => "subject",
                    "email_from" => "from email ",
                    "to_email" => "to email ",
                    "interval" => "2014-02-15 10:20:25",
                    "campaign_start_date" => "2014-02-15 10:20:25",
                    "token"=>array('afaf','afaf')
                ],
                [
                    "row_id" => 1001,
                    "campaign_id" => 7,
                    "user_id"=>2,
                    "company_id" => 50,
                    "firebase_token" => 'ABC123',
                    "device_token" => '987654',
                    "track_id" => 789,
                    "server_firebase_key" => 'SERVER85417ASDABC123',
                    "message_type" => "push",
                    "campaign_content" => "content of campaign text/html",
                    "email_subject" => "subject",
                    "email_from" => "from email ",
                    "to_email" => "to email ",
                    "interval" => "2014-02-15 10:20:25",
                    "campaign_start_date" => "2014-02-15 10:20:25"
                ],
                [
                    "row_id" => 1001,
                    "campaign_id" => 7,
                    "user_id"=>2,
                    "company_id" => 50,
                    "firebase_token" => 'ABC123',
                    "device_token" => '987654',
                    "track_id" => 789,
                    "server_firebase_key" => 'SERVER85417ASDABC123',
                    "message_type" => "push",
                    "campaign_content" => "content of campaign text/html",
                    "email_subject" => "subject",
                    "email_from" => "from email ",
                    "to_email" => "to email ",
                    "interval" => "2014-02-15 10:20:25",
                    "campaign_start_date" => "2014-02-15 10:20:25"
                ]
            ];
        $campaignId = 7;
        $rateResult = (new RateLimitingComponents)->rateLimitingRules($campaignId, $payload);
        dd($rateResult);
    }
}
