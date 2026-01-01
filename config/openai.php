<?php

return [
    'api_key' => env('OPENAI_API_KEY'),

    'api_url' => env('OPENAI_API_URL', 'https://api.openai.com/v1'),

    'models' => [
        'text' => env('OPENAI_TEXT_MODEL', 'gpt-4o-mini'),
        'text_premium' => env('OPENAI_TEXT_PREMIUM_MODEL', 'gpt-4o'),
        'vision' => env('OPENAI_VISION_MODEL', 'gpt-4o'),
        'image' => env('OPENAI_IMAGE_MODEL', 'dall-e-3'),
        // Agent Mode models
        'agent_default' => env('OPENAI_AGENT_MODEL', 'gpt-4o-mini'),
    ],

    'defaults' => [
        'max_tokens' => 4096,
        'temperature' => 0.7,
    ],

    'image' => [
        'quality' => [
            'low' => '512x512',
            'medium' => '1024x1024',
            'high' => '1792x1024',
        ],
    ],

    // Agent Mode settings
    'agent' => [
        'timeout' => env('OPENAI_AGENT_TIMEOUT', 120),
        'max_tokens' => env('OPENAI_AGENT_MAX_TOKENS', 4096),
        'temperature' => env('OPENAI_AGENT_TEMPERATURE', 0.7),
    ],
];
