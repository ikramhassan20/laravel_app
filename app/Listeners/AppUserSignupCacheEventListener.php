<?php

namespace App\Listeners;

use App\Events\AppUserSignupCacheEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Cache\AppUserLoginSignupCache;

class AppUserSignupCacheEventListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  AppUserSignupCacheEvent $event
     * @return void
     */
    public function handle(AppUserSignupCacheEvent $event)
    {
        $signup_cache = new AppUserLoginSignupCache();
        $signup_cache->saveAppUserSignupCache($event->params);
    }
}
