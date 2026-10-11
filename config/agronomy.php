<?php

return [
    'provider' => env('AGRONOMY_PROVIDER', 'fake'),
    'languages' => [
        'en' => 'English',
        'ha' => 'Hausa',
        'yo' => 'Yoruba',
        'ig' => 'Igbo',
        'pcm' => 'Nigerian Pidgin',
    ],
    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        'model' => env('AGRONOMY_GEMINI_MODEL', 'gemini-3.8-flash'),
        'connect_timeout' => (int) env('AGRONOMY_GEMINI_CONNECT_TIMEOUT', 5),
        'read_timeout' => (int) env('AGRONOMY_GEMINI_READ_TIMEOUT', 25),
    ],
];
