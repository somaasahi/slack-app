<?php

return [
    // Webhook URL
    'url' => env('SLACK_URL'),

    // チャンネル設定
    'default' => 'test',

    'channels' => [
        'test' => [
            'username' => 'テスト通知',
            'icon' => ':bulb:',
            'channel' => '02_develop_debug_log',
        ],
    ],

    'team'=>'TDWRKFV0S',

    'oauth' =>[
        'authorize_url' => 'https://slack.com/oauth/authorize',
        'oauth_url' => 'https://slack.com/api/oauth.access',
        'signing_secret' => env('SLACK_SIGNING_SECRET'),
        'client_secret' => env('SLACK_CLIENT_SECRET'),
        'client_id' => env('SLACK_CLIENT_ID'),
        'redirect_uri' => env('SLACK_REDIRECT_URI'),
    ],
];
