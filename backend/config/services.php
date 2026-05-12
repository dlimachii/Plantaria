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

    'nominatim' => [
        'base_url' => env('NOMINATIM_BASE_URL', 'https://nominatim.openstreetmap.org'),
        'user_agent' => env('NOMINATIM_USER_AGENT', 'Plantaria/1.0 (local geocoding proxy)'),
    ],

    'plantaria_analytics' => [
        'python_bin' => env('PLANTARIA_ANALYTICS_PYTHON', 'python3'),
    ],

    'plantaria_footprint' => [
        'carbon_intensity_g_per_kwh' => env('PLANTARIA_CARBON_INTENSITY_G_PER_KWH', 233),
        'idle_watts' => env('PLANTARIA_SERVER_IDLE_WATTS', 8),
        'cpu_peak_extra_watts' => env('PLANTARIA_SERVER_CPU_PEAK_EXTRA_WATTS', 32),
        'memory_watts_per_gb' => env('PLANTARIA_SERVER_MEMORY_WATTS_PER_GB', 0.35),
    ],

    'ollama' => [
        'enabled' => env('OLLAMA_ENABLED', true),
        'base_url' => env('OLLAMA_BASE_URL', 'http://127.0.0.1:11434'),
        'model' => env('OLLAMA_MODEL', 'llama3.2:1b'),
    ],

    'admin_sql' => [
        'enabled' => env('PLANTARIA_ADMIN_SQL_READONLY_ENABLED', true),
        'max_rows' => env('PLANTARIA_ADMIN_SQL_MAX_ROWS', 200),
        'max_length' => env('PLANTARIA_ADMIN_SQL_MAX_LENGTH', 4000),
        'timeout_ms' => env('PLANTARIA_ADMIN_SQL_TIMEOUT_MS', 4000),
    ],

];
