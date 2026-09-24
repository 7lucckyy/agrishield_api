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
];
