<?php

return [
    'provider' => env('VOICE_ASSISTANCE_PROVIDER', 'fake'),
    'transcription_provider' => env('VOICE_TRANSCRIPTION_PROVIDER', 'n_atlas'),
    'openai' => [
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'api_key' => env('OPENAI_API_KEY'),
        'transcription_model' => env('OPENAI_TRANSCRIPTION_MODEL', 'gpt-4o-mini-transcribe'),
        'connect_timeout' => (int) env('OPENAI_CONNECT_TIMEOUT', 10),
        'read_timeout' => (int) env('OPENAI_READ_TIMEOUT', 60),
    ],
    'n_atlas' => [
        'chat_completions_url' => env('NATLAS_CHAT_COMPLETIONS_URL'),
        'api_key' => env('NATLAS_API_KEY'),
        'model' => env('NATLAS_MODEL', 'NCAIR1/N-ATLaS'),
        'asr_endpoints' => [
            'en' => env('NATLAS_ENGLISH_ASR_URL'),
            'ha' => env('NATLAS_HAUSA_ASR_URL'),
            'yo' => env('NATLAS_YORUBA_ASR_URL'),
            'ig' => env('NATLAS_IGBO_ASR_URL'),
        ],
        'connect_timeout' => (int) env('NATLAS_CONNECT_TIMEOUT', 10),
        'read_timeout' => (int) env('NATLAS_READ_TIMEOUT', 90),
    ],
    'languages' => [
        'auto' => 'Detect automatically',
        'en' => 'English',
        'ha' => 'Hausa',
        'yo' => 'Yoruba',
        'ig' => 'Igbo',
        'ff' => 'Fulfulde',
        'kr' => 'Kanuri',
        'pcm' => 'Nigerian Pidgin',
        'ar' => 'Arabic',
    ],
    'response_languages' => [
        'en' => 'English',
        'ha' => 'Hausa',
        'yo' => 'Yoruba',
        'ig' => 'Igbo',
    ],
];
