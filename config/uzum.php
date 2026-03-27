<?php

// file: config/uzum.php

return [
    'base_url' => env('UZUM_API_BASE_URL', 'https://api-seller.uzum.uz/api/seller-openapi'),
    'timeout' => env('UZUM_API_TIMEOUT', 60),
    'verify_ssl' => env('UZUM_VERIFY_SSL', true), // Отключить для локальной разработки на Windows
    // OAuth2 (Seller Panel авторизация для отзывов и т.д.)
    'oauth_token_url' => env('UZUM_OAUTH_TOKEN_URL', 'https://api-seller.uzum.uz/api/oauth/token'),
    'oauth_client_id' => env('UZUM_OAUTH_CLIENT_ID', 'b2b-front'),
    'oauth_client_secret' => env('UZUM_OAUTH_CLIENT_SECRET', 'clientSecret'),

];
