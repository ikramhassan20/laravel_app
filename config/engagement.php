<?php

return [
    'api' => [
        'versions' => ['v1'],
        'headers' => [
            'prefix' => 'engagiv-',
            'app' => [
                'app-id',
                'app-name',
                'app-version',
                'app-build',
                'device-type',
                'user-token',
                'lang'
            ],
            'bulkApp' => [
                'app_id',
                'app_name',
                'app_version',
                'app_build',
                'device_type'
            ],
            'limit' => 100
        ],
        'notifications' => [
            'firebase_server_key' => env('FIREBASE_API_KEY'),
            'device_types' => ['android', 'ios', 'web'],
            'platforms' => ['android', 'ios', 'web', 'universal', 'email'],
            'notification_types' => ['email', 'inapp', 'push'],
            'inapp_types' => ['banner', 'dialog', 'full screen'],
            'inapp_dialogue_types' => ['top', 'middle', 'bottom'],
            'user_data_type' => ['conversion', 'action', 'user', 'app', 'gamification', 'custom'],
            'params' => [
                'push' => [
                    'title' => 'title',
                    'message' => 'body',
                    'target' => 'link'
                ],
                'inapp' => [
                    'inapp_code',
                    'message',
                    'campaign_code',
                    'campaign_type',
                    'track_key',
                    'view_link',
                    'params',
                    'message_type',
                    'message_position'
                ]
            ],
        ],
        'limit' => [
            'user_token' => env('USER_TOKEN_LIMIT', 200),
            'device_token_limit' => env('DEVICE_TOKEN_LIMIT', 1), //** to do */
            'tokens_limit' => env('DEVICE_TOKEN_LIMIT', 1000), //** to do */
            'email' => env('CAMPAIGN_EMAIL_LIMIT', 100),
            'inapp' => env('CAMPAIGN_INAPP_LIMIT', 100),
            'push' => env('CAMPAIGN_PUSH_LIMIT', 100),
            'segments' => env('SEGMENTS_LIMIT', 2),
            'queues' => env('QUEUES_LIMIT', 100),
            'user_attribute' => env('USER_ATTRIBUTE_LIMIT', 100),
            'dashboard_map_limit' => env('DASHBOARD_MAP_LIMIT', 300),
            'sp_limit' => env('SP_LIMIT', 50000),
            'redis_cache_limit' => env('REDIS_CACHE_LIMIT', 50000),
        ],
        'bulk_import' => [
            'chunk_size' => env('IMPORT_DATA_CHUNK_SIZE', 100)
        ],
        'export' => [
            'chunk_size' => env('EXPORT_DATA_CHUNK_SIZE', 10000)
        ],
        'dashboard_map' => [
            'chunk_size' => env('DASHBOARD_MAP_CHUNK_SIZE', 100)
        ],
        'log_api_response_time' => env('LOG_API_RESPONSE_TIME', false),
        'log_console_response_time' => env('LOG_CONSOLE_RESPONSE_TIME', false),
        'archive_campaign_tracking' => [
            'enabled' => env("ARCHIVE_CAMPAIGN_TRACKINGS_ENABLED", false),
            'chunkSize' => env("ARCHIVE_CAMPAIGN_TRACKINGS_CHUNK_SIZE", 500)
        ],
        'archive_board_tracking' => [
            'enabled' => env("ARCHIVE_BOARD_TRACKINGS_ENABLED", false),
            'chunkSize' => env("ARCHIVE_BOARD_TRACKINGS_CHUNK_SIZE", 500)
        ]

    ],
    'url' => [
        'auth' => env('AUTH_WEB_URL'),
        'authboard' => env('AUTH_BOARD_URL'),
        'inappview' => env('CAMPAIGN_INAPP_VIEW_URL'),
        'inappviewboard' => env('BOARD_INAPP_VIEW_URL'),
    ],
    'limit' => env('RECORDS_PER_PAGE', 20),
    'segments' => [
        'types' => ['user', 'action', 'conversion']
    ],
    'days_to_consider_user_inactive' => env('DAYS_USER_INACTIVE', 30),
    'custom_varchar_attributes' => env('CUSTOM_VARCHAR_ATTRIBUTES', ""),
    'currency_attribute' => [
        'values' => [
            'AED' => 'AED',
            'BHD' => 'BHD',
            'EGP' => 'EGP',
            'EUR' => 'EUR',
            'GBP' => 'GBP',
            'HKD' => 'HKD',
            'JOD' => 'JOD',
            'KWD' => 'KWD',
            'LBP' => 'LBP',
            'MYR' => 'MYR',
            'OMR' => 'OMR',
            'QAR' => 'QAR',
            'SAR' => 'SAR',
            'SGD' => 'SGD',
            'USD' => 'USD',
            'ZAR' => 'ZAR',
            'INR' => 'INR',
            'IDR' => 'IDR'

        ]
    ]
];