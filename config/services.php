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

    /*
    |--------------------------------------------------------------------------
    | ML Prediction Microservice (Phase 3 / M9)
    |--------------------------------------------------------------------------
    |
    | Python microservice (scikit-learn + transformers) that runs burnout-risk,
    | autonomy-loss, and intervention-report-analysis inference jobs. Laravel
    | dispatches jobs to this service and polls for results asynchronously.
    | The service is optional: if it is down the circuit breaker catches the
    | failure and requests remain in 'en_attente' state (shown as "en calcul"
    | in the UI) until the circuit recovers.
    |
    */
    'ml_service' => [
        'base_url' => env('ML_SERVICE_URL', 'http://localhost:8001'),
        'secret' => env('ML_SERVICE_SECRET', ''),
        'timeout' => (int) env('ML_SERVICE_TIMEOUT', 10),
    ],

];
