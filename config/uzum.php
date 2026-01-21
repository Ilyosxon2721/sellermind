<?php
// file: config/uzum.php

return [
    'base_url' => env('UZUM_API_BASE_URL', 'https://api-seller.uzum.uz/api/seller-openapi'),
    'timeout' => env('UZUM_API_TIMEOUT', 60),
    'verify_ssl' => env('UZUM_VERIFY_SSL', true), // Отключить для локальной разработки на Windows
    'auth' => [
        'header' => env('UZUM_AUTH_HEADER', 'Authorization'),
        // OpenAPI описывает передачу токена без Bearer-префикса
        'prefix' => env('UZUM_AUTH_PREFIX', ''),
    ],
    // Безопасный GET для проверки токена/доступности
    'ping_path' => env('UZUM_PING_PATH', '/v1/shops'),
    'ping_candidates' => [
        '/v1/shops',
    ],
    'status_map' => [
        'new' => 'new',
        'processing' => 'assembling',
        'ready_for_pickup' => 'shipping',
        'shipped' => 'shipping',
        'delivered' => 'archive',
        'canceled' => 'canceled',
        'returned' => 'canceled',
    ],

    // Rate limiting settings
    'rate_limit_delay_ms' => env('UZUM_RATE_LIMIT_DELAY_MS', 500), // Задержка между запросами в мс
    'rate_limit_retry_after' => env('UZUM_RATE_LIMIT_RETRY_AFTER', 60), // Ожидание при 429 в секундах
];
