<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'App\Events\AppUserLoginCacheEvent' => [
            'App\Listeners\AppUserLoginCacheEventListener',
        ],
        'App\Events\AppUserSignupCacheEvent' => [
            'App\Listeners\AppUserSignupCacheEventListener',
        ],
        'App\Events\AddSegmentCacheEvent' => [
            'App\Listeners\AddSegmentCacheEventListener',
        ],
        'App\Events\RemoveSegmentCacheEvent' => [
            'App\Listeners\RemoveSegmentCacheEventListener',
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
