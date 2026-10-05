<?php

return [
    'provider' => env('FARMING_PROVIDER', 'fake'),

    'providers' => [
        'openweather' => [
            'base_url' => env('OPENWEATHER_BASE_URL', 'https://api.openweathermap.org/data/2.5'),
            'api_key' => env('OPENWEATHER_API_KEY'),
            'connect_timeout' => (int) env('OPENWEATHER_CONNECT_TIMEOUT', 5),
            'read_timeout' => (int) env('OPENWEATHER_READ_TIMEOUT', 15),
            'cache_seconds' => (int) env('OPENWEATHER_CACHE_SECONDS', 900),
        ],
        'satyukt' => [
            'base_url' => env('SATYUKT_BASE_URL'),
            'credentials_ref' => 'SATYUKT_API_KEY',
            'connect_timeout' => (int) env('SATYUKT_CONNECT_TIMEOUT', 10),
            'read_timeout' => (int) env('SATYUKT_READ_TIMEOUT', 30),
        ],
        'fake' => [
            'seed' => (int) env('FAKE_PROVIDER_SEED', 1337),
            'latency_ms' => (int) env('FAKE_PROVIDER_LATENCY_MS', 0),
            'failure_rate' => (float) env('FAKE_PROVIDER_FAILURE_RATE', 0.0),
            'chaos' => (bool) env('FAKE_PROVIDER_CHAOS', false),
        ],
    ],

    'circuit' => [
        'cache_store' => env('FARMING_CIRCUIT_STORE', 'redis'),
        'failure_threshold' => (int) env('FARMING_CIRCUIT_FAILURE_THRESHOLD', 5),
        'cooldown_seconds' => (int) env('FARMING_CIRCUIT_COOLDOWN_SECONDS', 60),
    ],

    'freshness' => [
        'soil_health' => ['cadence' => 120, 'stale_after' => 180],
        'pest_warning' => ['cadence' => 7, 'stale_after' => 14],
        'irrigation' => ['cadence' => 1, 'stale_after' => 3],
        'weather' => ['cadence' => 1, 'stale_after' => 2],
        'ndvi' => ['cadence' => 5, 'stale_after' => 12],
        'lswi' => ['cadence' => 5, 'stale_after' => 12],
        'soil_moisture' => ['cadence' => 12, 'stale_after' => 24],
    ],

    'boundary' => [
        'max_points_per_ring' => 1000,
        'max_rings' => 20,
        'max_bytes' => 512 * 1024,
        'max_area_hectares' => 10000,
    ],
];
