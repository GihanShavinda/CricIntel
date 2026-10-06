<?php

return [
    'disk' => env('CRICINTEL_REPORT_DISK', 'local'),
    'directory' => env('CRICINTEL_REPORT_DIRECTORY', 'reports'),
    'queue' => env('CRICINTEL_REPORT_QUEUE', 'reports'),
    'tries' => (int) env('CRICINTEL_REPORT_TRIES', 3),
    'timeout' => (int) env('CRICINTEL_REPORT_TIMEOUT', 120),
    'backoff' => [30, 120, 300],
    'heavy_formats' => ['pdf', 'xlsx'],

    'types' => [
        'player_performance' => 'Player Performance Report',
        'match' => 'Match Report',
        'team_performance' => 'Team Performance Report',
        'opponent' => 'Opponent Report',
        'training' => 'Training Report',
        'scouting' => 'Scouting Report',
        'tournament' => 'Tournament Report',
        'tactical_preparation' => 'Tactical Preparation Report',
    ],

    'formats' => [
        'pdf' => 'PDF',
        'xlsx' => 'Excel',
        'csv' => 'CSV',
    ],
];
