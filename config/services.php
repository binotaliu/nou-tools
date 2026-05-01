<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'discord' => [
        'webhooks' => [
            'new_store' => env('DISCORD_WEBHOOK_URL_NEW_STORE'),
            'new_report' => env('DISCORD_WEBHOOK_URL_NEW_REPORT'),
            'new_comment' => env('DISCORD_WEBHOOK_URL_NEW_COMMENT'),
        ],
    ],

    'map' => [
        'tileLayer' => env('MAP_TILE_LAYER', 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'),
        'tileLayerAttribution' => env('MAP_TILE_LAYER_ATTRIBUTION', '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'),
    ],
];
