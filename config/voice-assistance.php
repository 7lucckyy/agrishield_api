<?php

return [
    'provider' => env('VOICE_ASSISTANCE_PROVIDER', 'fake'),
    'openai' => [
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'api_key' => env('OPENAI_API_KEY'),
        'transcription_model' => env('OPENAI_TRANSCRIPTION_MODEL', 'gpt-4o-mini-transcribe'),
        'guidance_model' => env('OPENAI_GUIDANCE_MODEL', 'gpt-4o-mini'),
    ],
    'languages' => [
        'auto' => 'Detect automatically',
        'en' => 'English',
        'ha' => 'Hausa',
        'yo' => 'Yoruba',
        'ig' => 'Igbo',
        'ff' => 'Fulfulde',
        'ar' => 'Arabic',
    ],
];
