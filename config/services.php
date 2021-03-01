<?php

return [

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'ses' => [
        'key' => env('SES_KEY'),
        'secret' => env('SES_SECRET'),
        'region' => env('SES_REGION', 'us-east-1'),
    ],

    'sparkpost' => [
        'secret' => env('SPARKPOST_SECRET'),
    ],

    'slack' => [
        'token' => env('SLACK_LEGACY_TOKEN'),
        'general_channel_id' => env('SLACK_GENERAL_CHANNEL'),
        'client_id' => env('SLACK_APP_CLIENT_ID'),
        'client_secret' => env('SLACK_APP_CLIENT_SECRET'),
        'team_id' => env('SLACK_TEAM_ID'),
        'signing_secret' => env('SLACK_APP_SIGNING_SECRET'),
    ],

    'stripe' => [
        'model' => App\User::class,
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook' => [
            'secret' => env('STRIPE_WEBHOOK_SECRET'),
            'tolerance' => env('STRIPE_WEBHOOK_TOLERANCE', 300),
        ],
    ],

];
