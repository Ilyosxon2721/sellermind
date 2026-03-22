<?php

declare(strict_types=1);

// file: config/uzum-crawler.php
// Конфигурация краулера/аналитики Uzum

return [
    // API endpoints (подтверждено через DevTools браузера uzum.uz)
    'api' => [
        'rest_base_url' => env('UZUM_REST_API_URL', 'https://api.uzum.uz/api'),
        'graphql_url'   => env('UZUM_GRAPHQL_URL', 'https://graphql.uzum.uz'),
        'timeout'       => env('UZUM_API_TIMEOUT', 30),
    ],

    // Базовый URL (backward compatibility)
    'base_url' => env('UZUM_CRAWLER_BASE_URL', 'https://api.uzum.uz'),

    // Сервер аутентификации (выдаёт анонимный JWT)
    'auth_server' => env('UZUM_AUTH_SERVER', 'https://id.uzum.uz'),

    // Rate limits (запросов в минуту)
    'rate_limits' => [
        'product'  => 10, // Карточка товара: max 10/мин
        'category' => 5,  // Список категории (GraphQL): max 5/мин
        'shop'     => 8,  // Данные магазина: max 8/мин
        'token'    => 2,  // Обновление токена: max 2/мин
    ],

    // Задержки между запросами (секунды, = 60 / rate_limit)
    'delays' => [
        'product'  => 6,
        'category' => 12,
        'shop'     => 8,
        'token'    => 30,
    ],

    // Jitter: случайная прибавка к задержке (base × random(0, jitter))
    'jitter' => 0.5,

    // Пул токенов
    'token_pool' => [
        'min_size'       => 3,                      // Минимум токенов в пуле
        'ttl_minutes'    => 12,                     // TTL JWT от Uzum
        'refresh_before' => 2,                      // Обновлять за N минут до истечения
        'max_requests'   => 8,                      // Максимум запросов на один токен
        'redis_prefix'   => 'uzum_crawler:token:',
    ],

    // Circuit breaker
    'circuit_breaker' => [
        'failure_threshold'  => 5,   // Ошибок до открытия цепи
        'window_minutes'     => 10,  // Окно наблюдения
        'pause_minutes'      => 60,  // Пауза краулера при срабатывании
        'alert_threshold'    => 3,   // Последовательных ошибок для Telegram алерта
        'redis_key'          => 'uzum_crawler:circuit_breaker',
    ],

    // Кэш ответов API
    'cache_ttl_minutes' => 30,

    // Redis префикс для кэша
    'redis_prefix' => 'uzum_crawler:',

    // Retry при ошибках
    'retry' => [
        'max_attempts'    => 3,
        'backoff_seconds' => [30, 60, 120], // Экспоненциальный backoff
    ],

    // Пул User-Agent строк
    'user_agents' => [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Safari/605.1.15',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    ],

    // URL для получения анонимного JWT.
    // POST /api/auth/token — подтверждено через DevTools браузера.
    // Токен хранится в localStorage["auth_sdk_access_token"], выдаётся id.uzum.uz.
    'token_url' => env('UZUM_CRAWLER_TOKEN_URL', 'https://api.uzum.uz/api/auth/token'),

    // Telegram чат для алертов краулера (отдельный от основного)
    'telegram_chat_id' => env('UZUM_CRAWLER_TELEGRAM_CHAT_ID', null),

    // Расписание краулера
    'schedule' => [
        'snapshots_per_day' => env('UZUM_SNAPSHOTS_PER_DAY', 4),
        'snapshot_times' => ['00:00', '06:00', '12:00', '18:00'], // UTC+5
        'categories_sync_cron' => env('UZUM_CATEGORIES_CRON', '0 3 * * *'),
        'token_refresh_cron' => env('UZUM_TOKEN_REFRESH_CRON', '*/5 * * * *'),
    ],

    // Лимиты по тарифам
    'limits' => [
        'free' => [
            'max_tracked_products' => 0,
            'snapshots_per_day' => 0,
        ],
        'pro' => [
            'max_tracked_products' => env('UZUM_LIMIT_PRO', 20),
            'snapshots_per_day' => 4,
        ],
        'business' => [
            'max_tracked_products' => env('UZUM_LIMIT_BUSINESS', 50),
            'snapshots_per_day' => 4,
        ],
    ],

    // Мониторинг и алерты
    'monitoring' => [
        'log_all_requests' => env('UZUM_LOG_REQUESTS', true),
        'telegram_alerts' => env('UZUM_TELEGRAM_ALERTS', true),
        'telegram_dev_chat_id' => env('UZUM_DEV_CHAT_ID'),
        'weekly_report_email' => env('UZUM_REPORT_EMAIL'),
    ],

    // Feature flags
    'features' => [
        'enabled' => env('UZUM_ANALYTICS_ENABLED', false),
        'beta_users_only' => env('UZUM_BETA_ONLY', true),
        'competitor_monitoring' => env('UZUM_FEATURE_COMPETITORS', true),
        'category_analytics' => env('UZUM_FEATURE_CATEGORIES', true),
        'price_alerts' => env('UZUM_FEATURE_ALERTS', true),
    ],
];
