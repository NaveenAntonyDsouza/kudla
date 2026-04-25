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

    'fast2sms' => [
        'api_key' => env('FAST2SMS_API_KEY'),
        'sender_id' => env('FAST2SMS_SENDER_ID', 'MATRIM'),
    ],

    'razorpay' => [
        'key' => env('RAZORPAY_KEY_ID'),
        'secret' => env('RAZORPAY_KEY_SECRET'),
        'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
    ],

    'stripe' => [
        // Publishable key — pk_test_... / pk_live_... — safe to ship to Flutter.
        'key' => env('STRIPE_KEY'),
        // Secret API key — sk_test_... / sk_live_... — server-only, used for HTTP Basic auth.
        'secret' => env('STRIPE_SECRET'),
        // Webhook signing secret — whsec_... — used to verify Stripe-Signature header.
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'paypal' => [
        // PayPal REST app credentials (Developer dashboard → My Apps & Credentials).
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'secret' => env('PAYPAL_SECRET'),

        // 'sandbox' or 'live' — determines the API base URL.
        'mode' => env('PAYPAL_MODE', 'sandbox'),

        // The webhook ID PayPal assigns when you register a webhook URL in
        // their dashboard. Used (with the transmission headers) to call
        // POST /v1/notifications/verify-webhook-signature for inbound auth.
        'webhook_id' => env('PAYPAL_WEBHOOK_ID'),

        // 3-letter ISO currency for the PayPal order. PayPal-India merchant
        // accounts cannot receive INR — default to USD. Buyers targeting
        // other markets override via SiteSetting / .env.
        'currency' => env('PAYPAL_CURRENCY', 'USD'),
    ],

    'cloudinary' => [
        'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
        'upload_preset' => env('CLOUDINARY_UPLOAD_PRESET'),
    ],

];
