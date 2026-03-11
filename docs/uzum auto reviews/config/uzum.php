<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Uzum Seller API
    |--------------------------------------------------------------------------
    */

    'api_base_url' => env('UZUM_API_BASE_URL', 'https://api-seller.uzum.uz/api/seller-openapi'),
    'api_token'    => env('UZUM_API_TOKEN', ''),
    'api_timeout'  => env('UZUM_API_TIMEOUT', 30),

    // Для seller panel API (отзывы и т.д.)
    'seller_panel_base_url' => env('UZUM_SELLER_PANEL_URL', 'https://api-seller.uzum.uz'),

    /*
    |--------------------------------------------------------------------------
    | OAuth2 (Seller Panel авторизация)
    |--------------------------------------------------------------------------
    | Для работы с отзывами и другими seller panel API
    | Используется OAuth2 Password Grant
    */

    'oauth_token_url'    => env('UZUM_OAUTH_TOKEN_URL', 'https://api-seller.uzum.uz/api/oauth/token'),
    'oauth_client_id'    => env('UZUM_OAUTH_CLIENT_ID', 'b2b-front'),
    'oauth_client_secret' => env('UZUM_OAUTH_CLIENT_SECRET', 'clientSecret'),

    /*
    |--------------------------------------------------------------------------
    | Авто-подтверждение заказов
    |--------------------------------------------------------------------------
    */

    'auto_confirm' => [
        'enabled'      => env('UZUM_AUTO_CONFIRM_ENABLED', true),
        'interval_min' => env('UZUM_AUTO_CONFIRM_INTERVAL', 15), // минуты
        'delay_sec'    => env('UZUM_AUTO_CONFIRM_DELAY', 0),     // задержка перед подтверждением
    ],

    /*
    |--------------------------------------------------------------------------
    | ИИ авто-ответ на отзывы
    |--------------------------------------------------------------------------
    */

    'review_ai_provider' => env('UZUM_REVIEW_AI_PROVIDER', 'anthropic'), // anthropic | openai
    'review_ai_api_key'  => env('UZUM_REVIEW_AI_API_KEY', ''),
    'review_ai_model'    => env('UZUM_REVIEW_AI_MODEL', 'claude-sonnet-4-20250514'),

    'auto_reply' => [
        'enabled'               => env('UZUM_AUTO_REPLY_ENABLED', false),
        'interval_min'          => env('UZUM_AUTO_REPLY_INTERVAL', 30), // минуты
        'min_rating_auto_reply' => env('UZUM_AUTO_REPLY_MIN_RATING', 1), // отвечать на все
        'require_approval'      => env('UZUM_AUTO_REPLY_REQUIRE_APPROVAL', false),
    ],
];
