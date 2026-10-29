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
    | Midtrans — QRIS dinamis (Sandbox)
    |--------------------------------------------------------------------------
    |
    | Kredensial hanya boleh dibaca dari .env dan dipakai di backend.
    | Mode produksi belum diaktifkan pada versi ini; nilai is_production
    | hanya menentukan host Snap yang dipanggil.
    |
    */

    'midtrans' => [
        'merchant_id' => env('MIDTRANS_MERCHANT_ID'),
        'client_key' => env('MIDTRANS_CLIENT_KEY'),
        'server_key' => env('MIDTRANS_SERVER_KEY'),
        'is_production' => (bool) env('MIDTRANS_IS_PRODUCTION', false),
        'snap_sandbox_url' => env(
            'MIDTRANS_SNAP_SANDBOX_URL',
            'https://app.sandbox.midtrans.com/snap/v1/transactions'
        ),
        'api_sandbox_url' => env('MIDTRANS_API_SANDBOX_URL', 'https://api.sandbox.midtrans.com/v2'),
    ],

];
