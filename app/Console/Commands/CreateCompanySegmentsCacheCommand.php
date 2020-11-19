<?php

namespace App\Console\Commands;

use App\Jobs\CreateCampaignSegmentsCacheJob;
use App\Jobs\CreateCompanySegmentsCacheJob;
use App\Role;
use App\User;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

class CreateCompanySegmentsCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'engagement:segments-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to create segments cache for company';

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
     * @throws \Exception
     *
     * @return mixed
     */
    public function handle()
    {
        $company_id = $this->option('company');
        $segment_id = $this->option('segment');
        
        $companies = isset($company_id) ? User::where('id',$company_id)->get() : Role::findByName('COMPANY')->users;

        foreach ($companies as $company) {
            $app_groups = $company->app_groups;

            if ($app_groups->count() > 0) {
                foreach ($app_groups as $app_group) {
                    $segments = $app_group->segments;

                    if ($segments->count() > 0 && isset($segment_id)) {
                        $segments = $segments->filter(function ($segment) use ($segment_id) {
                            return ($segment->id === $segment_id) ? $segment : null;
                        });
                    }

                    if ($segments->count() == 0) {
                        $this->error("No segments found for company id {$company->id}'s app group {$app_group->name}");
                    } else {
                        \Queue::pushOn('segmentscache', new CreateCompanySegmentsCacheJob($app_group));
                        \Queue::pushOn('segmentscache', new CreateCampaignSegmentsCacheJob($app_group));
                    }
                }
            } else {
                $this->error("No app groups created for company id {$company->id}");
            }
        }
    }


    protected function configure()
    {
        $this->addOption('company', null, InputOption::VALUE_OPTIONAL, 'Company ID');
        $this->addOption('segment', null, InputOption::VALUE_OPTIONAL, 'Segment ID');
    }
}
