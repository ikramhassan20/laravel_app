<?php
/**
 * Created by PhpStorm.
 * User: omair
 * Date: 2019-01-28
 * Time: 15:46
 */

return [
    'migration' => [
        'radius' => 100,
        'popular_newsfeed_count_limit' => 2,
        'template' => [
            'type' => [
                'email',
                'dialog',
                'full screen',
                'banner',
                'push',
                'newsfeed'
            ]
        ],
        'notification' => [
            'platform' => [
                'android',
                'ios',
                'web'
            ]
        ],
        'link_tracking' => [
            'device_type' => [
                'ios',
                'android',
                'web'
            ],
            'rec_type' => [
                'inapp',
                'push',
                'email',
                'newsfeed'
            ],
        ],
        'campaign' => [
            'status' => [
                'active',
                'draft',
                'expired',
                'suspended'
            ],
            'step' => [
                'general',
                'compose',
                'target',
                'delivery',
                'conversion',
                'preview'
            ],
            'schedule_type' => [
                'daily',
                'weekly',
                'once'
            ],
            'delivery_type' => [
                'schedule',
                'action',
                'api'
            ],
            'priority' => [
                'low',
                'medium',
                'high'
            ],
            'action_trigger_delay_unit' => [
                'second',
                'minute',
                'hour'
            ],
            'delivery_control_delay_unit' => [
                'minute',
                'day',
                'week',
                'month'
            ],
            'campaign_type' => [
                'email',
                'inapp',
                'push'
            ]
        ],
        'campaign_tracking' => [
            'status' => [
                'added',
                'executing',
                'completed',
                'failed'
            ],
            'device_type' => [
                'android',
                'ios',
                'web'
            ]
        ],
        'campaign_ratelimit_rule' => [
            'duration_unit' => [
                'minutes',
                'hours',
                'days',
                'weeks'
            ]
        ],
        'campaign_filter' => [
            'filter_type' => [
                'conversion',
                'action'
            ]
        ],
        'app_user_token' => [
            'device_type' => [
                'android',
                'ios',
                'web'
            ]
        ],
        'app' => [
            'platform' => [
                'android',
                'ios',
                'web'
            ]
        ],
        'user_type' => [
            'type' => [
                'conversion',
                'action',
                'user',
                'app',
                'gamification',
                'custom'
            ]
        ],
        'attribute' => [
            'data_type' => [
                'INT',
                'VARCHAR',
                'SELECT',
                'DATE'
            ],
            'attribute_type' => [
                'user',
                'action',
                'conversion'
            ]
        ],
        'campaign_queue' => [
            'status' => [
                'Available',
                'Processing',
                'Complete',
                'Failed'
            ],
            'priority' => [
                1,
                2,
                3
            ]
        ],
        'ratelimit' => [
            'duration_unit' => [
                'minutes',
                'days',
                'weeks'
            ]
        ],
        'ratelimit_log' => [
            'status' => [
                'pending',
                'inprogress',
                'completed',
                'failed'
            ]
        ],
        'lookup' => [
            'level' => [
                'platform',
                'company'
            ]
        ],
        'newsfeed' => [
            'category' => [
                'news',
                'advertising',
                'announcements',
                'social'
            ],
            'step' => [
                'compose',
                'delivery',
                'confirm'
            ],
            'status' => [
                'active',
                'draft',
                'suspend',
            ]
        ],
        'campaign_action' => [
            'action' => [
                'trigger',
                'conversion'
            ],
            'period' => ['minute', 'hour', 'day']
        ],
        'user' => [
            'cache_status' => [
                'inprocess',
                'completed'
            ]
        ]
    ]
];

