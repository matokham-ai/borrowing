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

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'base_url' => 'https://api.openai.com/v1',
    ],

    'google_vision' => [
        'api_key' => env('GOOGLE_CLOUD_VISION_API_KEY'),
    ],

    'ocrspace' => [
        // Free tier: 25,000 pages/month — get key at https://ocr.space/ocrapi/freekey
        'api_key' => env('OCR_SPACE_API_KEY', 'helloworld'), // 'helloworld' is OCR.space's public demo key
        'endpoint' => 'https://api.ocr.space/parse/image',
    ],

    // ── M-Pesa / Daraja ───────────────────────────────────────────────────
    'mpesa' => [
        'env'              => env('MPESA_ENV', 'production'),  // 'production' or 'sandbox'

        // C2B / STK Push
        'consumer_key'     => env('MPESA_CONSUMER_KEY'),
        'consumer_secret'  => env('MPESA_CONSUMER_SECRET'),
        'shortcode'        => env('MPESA_BUSINESS_SHORTCODE', '3014281'),
        'stk_shortcode'    => env('MPESA_STK_SHORTCODE', '4037237'),
        'passkey'          => env('MPESA_PASSKEY'),

        // B2C disbursement — separate app credentials
        'b2c_consumer_key'    => env('MPESA_B2C_CONSUMER_KEY'),
        'b2c_consumer_secret' => env('MPESA_B2C_CONSUMER_SECRET'),
        'b2c_shortcode'       => env('MPESA_B2C_SHORTCODE'),
        'initiator_name'      => env('MPESA_INITIATOR_NAME'),
        'security_credential' => env('MPESA_SECURITY_CREDENTIAL'),

        // Daraja must be able to reach these public HTTPS callbacks; when unset,
        // the application builds them from APP_URL and the organisation slug.
        'b2c_result_url'   => env('MPESA_B2C_RESULT_URL'),
        'b2c_timeout_url'  => env('MPESA_B2C_TIMEOUT_URL'),
        'stk_callback_url' => env('MPESA_STK_CALLBACK_URL'),

        // Additional Daraja product credentials. Keep these separate unless
        // Safaricom has enabled the relevant product on the same Daraja app.
        'reversal_consumer_key'    => env('MPESA_REVERSAL_CONSUMER_KEY'),
        'reversal_consumer_secret' => env('MPESA_REVERSAL_CONSUMER_SECRET'),
        'reversal_initiator'       => env('MPESA_REVERSAL_INITIATOR_NAME'),
        'reversal_security_credential' => env('MPESA_REVERSAL_SECURITY_CREDENTIAL'),
        'reversal_receiver_party'  => env('MPESA_REVERSAL_RECEIVER_PARTY'),

        'b2b_consumer_key'    => env('MPESA_B2B_CONSUMER_KEY'),
        'b2b_consumer_secret' => env('MPESA_B2B_CONSUMER_SECRET'),
        'b2b_initiator'       => env('MPESA_B2B_INITIATOR_NAME'),
        'b2b_security_credential' => env('MPESA_B2B_SECURITY_CREDENTIAL'),

        'operations_consumer_key'    => env('MPESA_OPERATIONS_CONSUMER_KEY'),
        'operations_consumer_secret' => env('MPESA_OPERATIONS_CONSUMER_SECRET'),
        'operations_initiator'       => env('MPESA_OPERATIONS_INITIATOR_NAME'),
        'operations_security_credential' => env('MPESA_OPERATIONS_SECURITY_CREDENTIAL'),

        'billmanager_consumer_key'    => env('MPESA_BILLMANAGER_CONSUMER_KEY'),
        'billmanager_consumer_secret' => env('MPESA_BILLMANAGER_CONSUMER_SECRET'),
    ],

];
