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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
        'mobile_client_id' => env('GOOGLE_MOBILE_CLIENT_ID', env('GOOGLE_CLIENT_ID')),
    ],

    'nusawork' => [
        'client_id' => env('NUSAWORK_CLIENT_ID'),
        'client_secret' => env('NUSAWORK_CLIENT_SECRET'),
        'public_key' => env('NUSAWORK_PUBLIC_KEY'),
        'export_candidate_url' => env('NUSAWORK_API_EXPORT_CANDIDATE_URL', '/emp/api/nusahire/candidates'),
        'master_data_path' => env('NUSAWORK_API_MASTER_DATA_PATH', '/emp/api/invitation-code/employee/data-company?company_structure=1&show_education=1'),
    ],

];
