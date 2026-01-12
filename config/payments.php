<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Click Payment Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Click payment gateway (Uzbekistan)
    | https://click.uz/
    |
    */
    'click' => [
        'merchant_id' => env('CLICK_MERCHANT_ID', ''),
        'service_id' => env('CLICK_SERVICE_ID', ''),
        'secret_key' => env('CLICK_SECRET_KEY', ''),
        'merchant_user_id' => env('CLICK_MERCHANT_USER_ID', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Payme Payment Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Payme (Paycom) payment gateway (Uzbekistan)
    | https://paycom.uz/
    |
    */
    'payme' => [
        'merchant_id' => env('PAYME_MERCHANT_ID', ''),
        'secret_key' => env('PAYME_SECRET_KEY', ''),
        'endpoint' => env('PAYME_ENDPOINT', 'https://checkout.paycom.uz'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Configuration
    |--------------------------------------------------------------------------
    |
    | General payment settings
    |
    */
    'default_currency' => env('PAYMENT_CURRENCY', 'UZS'),
    'transaction_timeout' => env('PAYMENT_TIMEOUT', 300), // seconds
];
