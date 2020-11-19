<?php

namespace App\Listeners;

use App\Events\AppUserLoginCacheEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Cache\AppUserLoginSignupCache;

class AppUserLogoutCacheEventListener
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
     * @param  AppUserLoginCacheEvent $event
     * @return void
     */
    public function handle(AppUserLogoutCacheEvent $event)
    {
        $login_cache = new AppUserLoginSignupCache();
        $login_cache->saveAppUserLogoutCache($event->params);
    }
}
