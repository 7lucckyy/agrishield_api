<?php

return [
    'gateway' => [
        'url' => env('ALERT_GATEWAY_URL'),
        'token' => env('ALERT_GATEWAY_TOKEN'),
        'connect_timeout' => (int) env('ALERT_GATEWAY_CONNECT_TIMEOUT', 5),
        'read_timeout' => (int) env('ALERT_GATEWAY_READ_TIMEOUT', 15),
    ],
];
