<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class BaseCommand extends Command
{
    /** @var string  */
    protected $name = 'BaseCommand';

    /** @var string  */
    protected $description = 'Base Command for all other commands';

    /**
     * BaseCommand constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    protected function terminate()
    {
        $isLog = config('engagement.api.log_console_response_time');
        if($isLog){
            $response_time = (microtime(true) - LARAVEL_START)*1000;
            \App\ResponseLog::logData([
                "name" => $this->signature,
                "type" => 'console',
                "console_type" => 'other',
                "response_time" => $response_time
            ]);
        }
    }

    protected function logResponse($responseTime, $recordId, $companyId, $consoleType)
    {
        $isLog = config('engagement.api.log_console_response_time');
        if($isLog){
            \App\ResponseLog::logData([
                "company_id" => $companyId,
                "record_id" => $recordId,
                "name" => $this->signature,
                "type" => 'console',
                "console_type" => $consoleType,
                "response_time" => $responseTime
            ]);
        }

    }


}
