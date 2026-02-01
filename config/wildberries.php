<?php
// file: config/wildberries.php

return [

    /*
    |--------------------------------------------------------------------------
    | Wildberries API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Wildberries marketplace API integration.
    | WB uses different domains for different API categories.
    |
    */

    'sandbox' => env('WB_USE_SANDBOX', false),

    // Base URLs for production (WB uses different domains for different API categories)
    'base_urls' => [
        'common'      => 'https://common-api.wildberries.ru',
        'content'     => 'https://content-api.wildberries.ru',
        'marketplace' => 'https://marketplace-api.wildberries.ru',
        'prices'      => 'https://discounts-prices-api.wildberries.ru',
        'statistics'  => 'https://statistics-api.wildberries.ru',
        'analytics'   => 'https://seller-analytics-api.wildberries.ru',
        'adv'         => 'https://advert-api.wildberries.ru',
        'feedbacks'   => 'https://feedbacks-api.wildberries.ru',
        'questions'   => 'https://questions-api.wildberries.ru',
    ],

    // Sandbox base URLs (same as production for WB)
    'sandbox_base_urls' => [
        'common'      => 'https://common-api.wildberries.ru',
        'content'     => 'https://content-api.wildberries.ru',
        'marketplace' => 'https://marketplace-api.wildberries.ru',
        'prices'      => 'https://discounts-prices-api.wildberries.ru',
        'statistics'  => 'https://statistics-api.wildberries.ru',
        'analytics'   => 'https://seller-analytics-api.wildberries.ru',
        'adv'         => 'https://advert-api.wildberries.ru',
        'feedbacks'   => 'https://feedbacks-api.wildberries.ru',
        'questions'   => 'https://questions-api.wildberries.ru',
    ],

    'timeout' => 30,
    'verify_ssl' => env('WB_VERIFY_SSL', true), // Disable for local development on Windows

    // Default tokens (if not using per-account tokens)
    'tokens' => [
        'content'     => env('WB_CONTENT_TOKEN'),
        'marketplace' => env('WB_MARKETPLACE_TOKEN'),
        'prices'      => env('WB_PRICES_TOKEN'),
        'statistics'  => env('WB_STATISTICS_TOKEN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | API Rate Limits
    |--------------------------------------------------------------------------
    |
    | WB has different rate limits for different API categories
    |
    */

    'rate_limits' => [
        'content'     => 100, // requests per minute
        'marketplace' => 60,
        'prices'      => 100,
        'statistics'  => 60,
        'analytics'   => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Settings (при получении 429 Rate Limit)
    |--------------------------------------------------------------------------
    */

    'retry' => [
        'max_attempts' => 3,          // максимум повторных попыток
        'delays'       => [5, 15, 30], // задержки между попытками (секунды)
        'max_delay'    => 120,         // максимальная задержка (секунды)
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync Settings
    |--------------------------------------------------------------------------
    */

    'sync' => [
        'products_per_page' => 100,   // max 100 for content API
        'orders_per_page' => 1000,    // max 1000 for orders API
        'stocks_batch_size' => 1000,  // max items per stocks update
    ],
];
