<?php

return [
    'api_key' => env('ANTHROPIC_API_KEY'),

    'api_url' => env('ANTHROPIC_API_URL', 'https://api.anthropic.com/v1'),

    'models' => [
        'text' => env('ANTHROPIC_TEXT_MODEL', 'claude-sonnet-4-20250514'),
        'text_premium' => env('ANTHROPIC_TEXT_PREMIUM_MODEL', 'claude-opus-4-20250514'),
        'kpi' => env('ANTHROPIC_KPI_MODEL', 'claude-sonnet-4-20250514'),
    ],

    'defaults' => [
        'max_tokens' => 4096,
        'temperature' => 0.7,
    ],
];
