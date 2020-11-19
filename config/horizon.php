<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Connection
    |--------------------------------------------------------------------------
    |
    | This is the name of the Redis connection where Horizon will store the
    | meta information required for it to function. It includes the list
    | of supervisors, failed jobs, job metrics, and other information.
    |
    */

    'use' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Prefix
    |--------------------------------------------------------------------------
    |
    | This prefix will be used when storing all Horizon data in Redis. You
    | may modify the prefix when you are running multiple installations
    | of Horizon on the same server so that they don't have problems.
    |
    */

    'prefix' => env('HORIZON_PREFIX', 'horizon:'),

    /*
    |--------------------------------------------------------------------------
    | Queue Wait Time Thresholds
    |--------------------------------------------------------------------------
    |
    | This option allows you to configure when the LongWaitDetected event
    | will be fired. Every connection / queue combination may have its
    | own, unique threshold (in seconds) before this event is fired.
    |
    */

    'waits' => [
        'redis:default' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Job Trimming Times
    |--------------------------------------------------------------------------
    |
    | Here you can configure for how long (in minutes) you desire Horizon to
    | persist the recent and failed jobs. Typically, recent jobs are kept
    | for one hour while all failed jobs are stored for an entire week.
    |
    */

    'trim' => [
        'recent' => 60,
        'failed' => 10080,
    ],

    /*
    |--------------------------------------------------------------------------
    | Fast Termination
    |--------------------------------------------------------------------------
    |
    | When this option is enabled, Horizon's "terminate" command will not
    | wait on all of the workers to terminate unless the --wait option
    | is provided. Fast termination can shorten deployment delay by
    | allowing a new instance of Horizon to start while the last
    | instance will continue to terminate each of its workers.
    |
    */

    'fast_termination' => false,

    /*
    |--------------------------------------------------------------------------
    | Queue Worker Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may define the queue worker settings used by your application
    | in all environments. These supervisors and settings handle all your
    | queued jobs and will be provisioned by Horizon during deployment.
    |
    */

    'environments' => [
        'production' => [
            'db-sync' => [
                'connection' => 'redis',
                'queue' => ['dbsync'],
                'balance' => 'simple',
                'processes' => 10,
                'sleep' => 3,
                'tries' => 1
            ],
            'segments-cache' => [
                'connection' => 'redis',
                'queue' => ['segmentcache'],
                'balance' => 'simple',
                'processes' => 10,
                'sleep' => 3,
                'tries' => 1
            ],
            'inapp' => [
                'connection' => 'redis',
                'queue' => ['inapphigh','inappmedium','inapplow','inapphighvariant2','inappmediumvariant2','inapplowvariant2','inapphighvariant3','inappmediumvariant3','inapplowvariant3','inapphighvariant4','inappmediumvariant4','inapplowvariant4'],
                'balance' => 'simple',
                'processes' => 10,
                'sleep' => 3,
                'tries' => 1
            ],
            'push' => [
                'connection' => 'redis',
                'queue' => ['pushhigh','pushmedium','pushlow','pushhighvariant2','pushmediumvariant2','pushlowvariant2','pushhighvariant3','pushmediumvariant3','pushlowvariant3','pushhighvariant4','pushmediumvariant4','pushlowvariant4'],
                'balance' => 'simple',
                'processes' => 10,
                'sleep' => 3,
                'tries' => 1
            ],
            'email' => [
                'connection' => 'redis',
                'queue' => ['emailhigh','emailmedium','emaillow','emailhighvariant2','emailmediumvariant2','emaillowvariant2','emailhighvariant3','emailmediumvariant3','emaillowvariant3','emailhighvariant4','emailmediumvariant4','emaillowvariant4'],
                'balance' => 'simple',
                'processes' => 10,
                'sleep' => 3,
                'tries' => 1
            ],
            'import' => [
                'connection' => 'redis',
                'queue' => ['import'],
                'balance' => 'simple',
                'processes' => 10,
                'sleep' => 3,
                'tries' => 1
            ],
            'export_users' => [
                'connection' => 'redis',
                'queue' => ['export_users'],
                'balance' => 'simple',
                'processes' => 10,
                'sleep' => 3,
                'tries' => 1
            ],
            'rebuild_cache' => [
                'connection' => 'redis',
                'queue' => ['rebuild_cache'],
                'balance' => 'simple',
                'processes' => 10,
                'sleep' => 3,
                'tries' => 1
            ],
        ],

        'local' => [
            'db-sync' => [
                'connection' => 'redis',
                'queue' => ['dbsync'],
                'balance' => 'simple',
                'processes' => 10,
                'sleep' => 3,
                'tries' => 1
            ],
            'segments-cache' => [
                'connection' => 'redis',
                'queue' => ['segmentcache'],
                'balance' => 'simple',
                'processes' => 10,
                'sleep' => 3,
                'tries' => 1
            ],
            'inapp' => [
                'connection' => 'redis',
                'queue' => ['inapphigh','inappmedium','inapplow','inapphighvariant2','inappmediumvariant2','inapplowvariant2','inapphighvariant3','inappmediumvariant3','inapplowvariant3','inapphighvariant4','inappmediumvariant4','inapplowvariant4'],
                'balance' => 'simple',
                'processes' => 10,
                'sleep' => 3,
                'tries' => 1
            ],
            'push' => [
                'connection' => 'redis',
                'queue' => ['pushhigh','pushmedium','pushlow','pushhighvariant2','pushmediumvariant2','pushlowvariant2','pushhighvariant3','pushmediumvariant3','pushlowvariant3','pushhighvariant4','pushmediumvariant4','pushlowvariant4'],
                'balance' => 'simple',
                'processes' => 10,
                'sleep' => 3,
                'tries' => 1
            ],
            'email' => [
                'connection' => 'redis',
                'queue' => ['emailhigh','emailmedium','emaillow','emailhighvariant2','emailmediumvariant2','emaillowvariant2','emailhighvariant3','emailmediumvariant3','emaillowvariant3','emailhighvariant4','emailmediumvariant4','emaillowvariant4'],
                'balance' => 'simple',
                'processes' => 10,
                'sleep' => 3,
                'tries' => 1
            ],
            'import' => [
                'connection' => 'redis',
                'queue' => ['import'],
                'balance' => 'simple',
                'processes' => 10,
                'sleep' => 3,
                'tries' => 1
            ],
            'export_users' => [
                'connection' => 'redis',
                'queue' => ['export_users'],
                'balance' => 'simple',
                'processes' => 10,
                'sleep' => 3,
                'tries' => 1
            ],
            'rebuild_cache' => [
                'connection' => 'redis',
                'queue' => ['rebuild_cache'],
                'balance' => 'simple',
                'processes' => 10,
                'sleep' => 3,
                'tries' => 1
            ],
        ],
    ],
];
