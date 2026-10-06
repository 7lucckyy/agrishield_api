<?php

return [
    'provider' => env('DIAGNOSIS_PROVIDER', 'fake'),

    'openai' => [
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_VISION_MODEL', 'gpt-4o-mini'),
        'connect_timeout' => (int) env('OPENAI_CONNECT_TIMEOUT', 10),
        'read_timeout' => (int) env('OPENAI_READ_TIMEOUT', 60),
    ],
    'gemini' => [
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_VISION_MODEL', 'gemini-2.5-flash'),
        'connect_timeout' => (int) env('GEMINI_CONNECT_TIMEOUT', 10),
        'read_timeout' => (int) env('GEMINI_READ_TIMEOUT', 45),
    ],
];
