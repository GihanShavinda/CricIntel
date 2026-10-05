<?php

return [
    'enabled' => env('PREDICTIVE_ANALYTICS_ENABLED', true),

    'base_url' => env(
        'PREDICTIVE_ANALYTICS_URL',
        'http://127.0.0.1:8100'
    ),

    'timeout_seconds' => (int) env(
        'PREDICTIVE_ANALYTICS_TIMEOUT',
        15
    ),

    'training_timeout_seconds' => (int) env(
        'PREDICTIVE_ANALYTICS_TRAINING_TIMEOUT',
        180
    ),
];
