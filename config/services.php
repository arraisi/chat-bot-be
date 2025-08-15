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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
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

    'upload_api' => [
        'url' => env('UPLOAD_API_URL', 'http://10.30.14.40:8888/predict'),
        'timeout' => env('UPLOAD_API_TIMEOUT', 120),
        'max_retries' => env('UPLOAD_API_MAX_RETRIES', 3),
    ],

    'chatbot_api' => [
        'url' => env('CHATBOT_API_URL', 'http://10.30.14.40:8889/predict'),
        'timeout' => env('CHATBOT_API_TIMEOUT', 60),
    ],

];
