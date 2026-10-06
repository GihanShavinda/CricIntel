<?php

return [
    'max_query_length' => (int) env(
        'NL_ANALYTICS_MAX_QUERY_LENGTH',
        800
    ),

    'max_rows' => (int) env(
        'NL_ANALYTICS_MAX_ROWS',
        50
    ),

    /*
    |--------------------------------------------------------------------------
    | Supported intents
    |--------------------------------------------------------------------------
    |
    | P16 deliberately supports a finite analytics vocabulary.
    | No generated SQL or arbitrary database access is permitted.
    |
    */
    'intents' => [
        'rank_bowlers_phase_vs_hand',
        'team_run_rate_trend',
        'batter_phase_performance',
        'bowler_phase_performance',
        'player_form_trend',
        'team_phase_scoring',
        'matchup_summary',
        'venue_scoring_summary',
        'opponent_phase_threats',
    ],

    'metrics' => [
        'economy',
        'wickets',
        'wicket_rate',
        'strike_rate',
        'run_rate',
        'runs',
        'average',
        'balls',
        'dots',
        'dot_ball_pct',
        'boundaries',
        'boundary_pct',
        'dismissals',
        'first_innings_total',
    ],

    'entities' => [
        'bowler',
        'batter',
        'player',
        'team',
        'matchup',
        'venue',
    ],

    'phases' => [
        'all',
        'powerplay',
        'middle',
        'death',
    ],

    'batting_hands' => [
        'all',
        'left',
        'right',
    ],

    'sort_directions' => [
        'asc',
        'desc',
    ],
];
