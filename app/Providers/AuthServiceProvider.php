<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;
use Carbon\Carbon;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        //
        Passport::routes();

        Passport::tokensCan([
            'platform' => 'Access Engagement Platform'
        ]);

        Passport::tokensExpireIn(Carbon::now()->addDays(2));
        Passport::personalAccessTokensExpireIn(Carbon::now()->addDays(2));
        //Passport::personalAccessTokensExpireIn(now()->addMinute(1));
    }
}
