<?php

return [
    'queue' => env('CRICINTEL_NOTIFICATIONS_QUEUE', 'notifications'),
    'tries' => (int) env('CRICINTEL_NOTIFICATIONS_TRIES', 4),
    'timeout' => (int) env('CRICINTEL_NOTIFICATIONS_TIMEOUT', 30),

    'backoff' => array_values(array_filter(array_map(
        'intval',
        explode(',', env('CRICINTEL_NOTIFICATIONS_BACKOFF', '30,120,300'))
    ))),

    'types' => [
        'squad_announced' => [
            'label' => 'Squad announced',
            'email_default' => true,
            'realtime_default' => true,
        ],
        'player_selected' => [
            'label' => 'Player selected',
            'email_default' => true,
            'realtime_default' => true,
        ],
        'player_removed' => [
            'label' => 'Player removed',
            'email_default' => true,
            'realtime_default' => true,
        ],
        'training_assigned' => [
            'label' => 'Training assigned',
            'email_default' => true,
            'realtime_default' => true,
        ],
        'training_changed' => [
            'label' => 'Training changed',
            'email_default' => true,
            'realtime_default' => true,
        ],
        'fixture_changed' => [
            'label' => 'Fixture changed',
            'email_default' => true,
            'realtime_default' => true,
        ],
        'match_starting' => [
            'label' => 'Match starting',
            'email_default' => true,
            'realtime_default' => true,
        ],
        'tactical_report_ready' => [
            'label' => 'Tactical report ready',
            'email_default' => true,
            'realtime_default' => true,
        ],
        'player_availability_changed' => [
            'label' => 'Player availability changed',
            'email_default' => false,
            'realtime_default' => true,
        ],
        'player_marked_injured' => [
            'label' => 'Player marked injured',
            'email_default' => true,
            'realtime_default' => true,
        ],
        'analyst_mentioned' => [
            'label' => 'Analyst mentioned',
            'email_default' => false,
            'realtime_default' => true,
        ],
        'coach_comment_added' => [
            'label' => 'Coach comment added',
            'email_default' => false,
            'realtime_default' => true,
        ],
    ],
];
