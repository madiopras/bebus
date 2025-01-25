<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Midtrans Configuration
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials and configuration for Midtrans
    |
    */

    'merchant_id' => env('MIDTRANS_MERCHANT_ID'),
    'client_key' => env('MIDTRANS_CLIENT_KEY'),
    'server_key' => env('MIDTRANS_SERVER_KEY'),

    'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
    'is_sanitized' => env('MIDTRANS_IS_SANITIZED', true),
    'is_3ds' => env('MIDTRANS_IS_3DS', true),

    /*
    |--------------------------------------------------------------------------
    | Midtrans Payment Check Configuration
    |--------------------------------------------------------------------------
    |
    | This section is for configuring the automatic payment status check
    |
    */

    // Enable/disable automatic payment status check
    'enable_payment_check' => env('MIDTRANS_ENABLE_PAYMENT_CHECK', false),

    // Notification URL
    'notification_url' => env('MIDTRANS_NOTIFICATION_URL'),

    // Payment completed redirect URL
    'finish_redirect_url' => env('MIDTRANS_FINISH_REDIRECT_URL'),

    // Payment unfinished redirect URL  
    'unfinish_redirect_url' => env('MIDTRANS_UNFINISH_REDIRECT_URL'),

    // Payment error redirect URL
    'error_redirect_url' => env('MIDTRANS_ERROR_REDIRECT_URL'),
];