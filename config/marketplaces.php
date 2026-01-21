<?php
// file: config/marketplaces.php

return [
    /*
    |--------------------------------------------------------------------------
    | Marketplace Configurations
    |--------------------------------------------------------------------------
    |
    | Configuration for each marketplace API integration.
    | Base URLs and settings can be overridden via environment variables.
    |
    */

    'wildberries' => [
        'code' => 'wb',
        'name' => 'Wildberries',
        'base_url' => env('WB_API_BASE_URL', 'https://marketplace-api.wildberries.ru'),
        'content_url' => env('WB_CONTENT_URL', 'https://content-api.wildberries.ru'),
        'statistics_url' => env('WB_STATISTICS_URL', 'https://statistics-api.wildberries.ru'),
        'discounts_prices_url' => env('WB_DISCOUNTS_PRICES_URL', 'https://discounts-prices-api.wildberries.ru'),
        'auth_type' => 'api_key', // API key in Authorization header
        'auth_header' => 'Authorization',
        'verify_ssl' => env('WB_VERIFY_SSL', true), // Disable for local development on Windows
        'rate_limit' => [
            'requests_per_minute' => 60,
        ],
    ],

    'ozon' => [
        'code' => 'ozon',
        'name' => 'Ozon',
        'base_url' => env('OZON_API_BASE_URL', 'https://api-seller.ozon.ru'),
        'auth_type' => 'client_credentials', // Client-Id + Api-Key headers
        'auth_headers' => [
            'client_id' => 'Client-Id',
            'api_key' => 'Api-Key',
        ],
        'verify_ssl' => env('OZON_VERIFY_SSL', true), // Disable for local development on Windows
        'rate_limit' => [
            'requests_per_minute' => 60,
        ],
    ],

    'uzum' => [
        'code' => 'uzum',
        'name' => 'Uzum Market',
        'base_url' => env('UZUM_API_BASE_URL', 'https://api-seller.uzum.uz/api/seller-openapi'),
        'auth_type' => 'api_key', // API key from seller cabinet
        'auth_header' => 'Authorization',
        'auth_prefix' => '', // Uzum API requires token WITHOUT Bearer prefix
        'verify_ssl' => env('UZUM_VERIFY_SSL', true), // Disable for local development on Windows
        'rate_limit' => [
            'requests_per_minute' => 100,
        ],
    ],

    'yandex_market' => [
        'code' => 'ym',
        'name' => 'Yandex Market',
        'base_url' => env('YANDEX_MARKET_API_BASE_URL', 'https://api.partner.market.yandex.ru'),
        'auth_type' => 'api_key', // Api-Key header
        'auth_header' => 'Api-Key',
        'auth_prefix' => '',
        'rate_limit' => [
            'requests_per_minute' => 100,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync Settings
    |--------------------------------------------------------------------------
    */

    'sync' => [
        // Default interval for automatic sync (in minutes)
        'interval' => env('MARKETPLACE_SYNC_INTERVAL', 60),

        // Maximum products per sync batch
        'batch_size' => env('MARKETPLACE_SYNC_BATCH_SIZE', 100),

        // Retry failed syncs
        'retry_attempts' => env('MARKETPLACE_SYNC_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('MARKETPLACE_SYNC_RETRY_DELAY', 60), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Credential Fields by Marketplace
    |--------------------------------------------------------------------------
    |
    | Defines which credential fields are required for each marketplace
    |
    */

    'credential_fields' => [
        'wb' => [
            'api_token' => ['label' => '✅ API токен (универсальный) - РЕКОМЕНДУЕТСЯ', 'type' => 'password', 'required' => false, 'hint' => '👍 Универсальный токен с доступом ко всем API. Это самый простой и удобный способ.'],
            'wb_content_token' => ['label' => '⚙️ Content API Token (Товары) - опционально', 'type' => 'password', 'required' => false, 'hint' => 'Только если не используете универсальный токен'],
            'wb_marketplace_token' => ['label' => '⚙️ Marketplace API Token (Заказы) - опционально', 'type' => 'password', 'required' => false, 'hint' => 'Только если не используете универсальный токен'],
            'wb_prices_token' => ['label' => '⚙️ Prices API Token (Цены) - опционально', 'type' => 'password', 'required' => false, 'hint' => 'Только если не используете универсальный токен'],
            'wb_statistics_token' => ['label' => '⚙️ Statistics API Token (Аналитика) - опционально', 'type' => 'password', 'required' => false, 'hint' => 'Только если не используете универсальный токен'],
        ],
        'ozon' => [
            'client_id' => ['label' => 'Client ID', 'type' => 'text', 'required' => true],
            'api_key' => ['label' => 'API ключ', 'type' => 'password', 'required' => true],
        ],
        'uzum' => [
            'api_token' => ['label' => 'API токен', 'type' => 'password', 'required' => true, 'hint' => 'Токен для доступа к API Uzum Market'],
        ],
        'ym' => [
            'oauth_token' => ['label' => 'API-ключ / OAuth токен', 'type' => 'password', 'required' => true, 'hint' => 'Создайте в личном кабинете Яндекс.Маркет: Настройки → API → Сгенерировать новый API-ключ'],
            'campaign_id' => ['label' => 'ID кампании', 'type' => 'text', 'required' => true, 'hint' => 'Числовой идентификатор вашего магазина на Яндекс.Маркет'],
            'business_id' => ['label' => 'Business ID (опционально)', 'type' => 'text', 'required' => false, 'hint' => 'Для работы с товарами через новое API (опционально)'],
        ],
    ],
];
