<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Proxy Configuration
    |--------------------------------------------------------------------------
    |
    | This value is used for proxy identification in the application.
    |
    */

    'proxy_key' => env('PROXY_KEY', 'proxy_id'),

    /*
    |--------------------------------------------------------------------------
    | Admin Path Configuration
    |--------------------------------------------------------------------------
    |
    | This value defines the admin path for the frontend application.
    |
    */

    'admin_path' => env('VITE_PATH_ADMIN', 'admin'),

    /*
    |--------------------------------------------------------------------------
    | Discord Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | This value is used for Discord webhook notifications.
    |
    */

    'discord_webhook_url' => env('DISCORD_WEBHOOK_URL'),

    'portal_slug_change_cooldown_days' => (int) env('PORTAL_SLUG_CHANGE_COOLDOWN_DAYS', 30),

    'job_vacancy_slug_change_cooldown_days' => (int) env('JOB_VACANCY_SLUG_CHANGE_COOLDOWN_DAYS', 30),

];
