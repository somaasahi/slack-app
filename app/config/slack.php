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
];
