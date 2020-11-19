<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\AddItemsToCampaignQueuesCommand::class,
        Commands\CreateCompanySegmentsCacheCommand::class,
        Commands\RateLimitingCommand::class,
        Commands\exportUsers::class,
        Commands\SegmentsDataCacheCommand::class,
        Commands\CampaignQueueUsersCommand::class,
        Commands\DispatchCampaignQueuesCommand::class,
        Commands\CampaignSegmentCommand::class,
        //Commands\DashboardStatsCacheCommand::class,
        Commands\AppUsersCacheCleanCommand::class,
        Commands\AppUsersArchiveCommand::class,
        Commands\DispatchBoardQueuesCommand::class,
        Commands\RemoveExpireTokens::class,
        Commands\RemoveOnceBoardCache::class,
        Commands\BoardQueueUsersCommand::class,
        Commands\RemoveCampaignUnionCache::class,
        Commands\RefreshRevokedTokens::class,
        Commands\RemoveInactiveUsers::class,
        Commands\ProcessRevokedTokens::class,
        Commands\RemoveExpiredCampaignCacheData::class,
        Commands\RemoveExpiredBoardCacheData::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        /**
         * @var $schedule Schedule
         */
        $schedule->command('campaign:campaign:dispatch')
            ->everyMinute();
        $schedule->command('app_users:cache:clean')
            ->dailyAt('01:00');
        $schedule->command('segment:cache')
            ->twiceDaily(3, 17);
        $schedule->command('board:dispatch')
            ->everyMinute();
        $schedule->command('expire-tokens:remove')
            ->weeklyOn(5, '8:00');
        $schedule->command('expired-campaign:cache-remove')
            ->daily();
        $schedule->command('expired-board:cache-remove')
            ->daily();
        $schedule->command('revoked-tokens:process')
            ->hourly();
        $schedule->command('inactive-users:remove')
            ->daily();
        /*$schedule->command('once-board-cache:remove')
            ->everyFifteenMinutes();
        $schedule->command('campaign-union-cache:remove')
            ->everyFifteenMinutes();*/
        //$schedule->command('campaign:create_queues')
        //    ->everyMinute();
        //$schedule->command('dashboard:stats')
        //    ->hourly();
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
