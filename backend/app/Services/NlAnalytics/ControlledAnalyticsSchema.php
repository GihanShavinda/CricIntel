<?php

namespace App\Services\NlAnalytics;

class ControlledAnalyticsSchema
{
    public function defaults(): array
    {
        return [
            'intent' => null,
            'metric' => null,
            'entity' => null,

            'team_id' => null,
            'team_name' => null,

            'season_id' => null,
            'season_name' => null,

            'opponent_team_id' => null,
            'opponent_name' => null,

            'venue_id' => null,
            'venue_name' => null,

            'format' => null,
            'phase' => 'all',
            'batting_hand' => 'all',

            'player_id' => null,
            'player_name' => null,

            'batter_id' => null,
            'batter_name' => null,

            'bowler_id' => null,
            'bowler_name' => null,

            'date_from' => null,
            'date_to' => null,

            'last_n_matches' => null,
            'limit' => 10,
            'sort_direction' => null,
        ];
    }

    public function supportedIntents(): array
    {
        return config('nl_analytics.intents', []);
    }

    public function supportedMetrics(): array
    {
        return config('nl_analytics.metrics', []);
    }

    public function supportedEntities(): array
    {
        return config('nl_analytics.entities', []);
    }

    public function supportedPhases(): array
    {
        return config('nl_analytics.phases', []);
    }

    public function supportedBattingHands(): array
    {
        return config('nl_analytics.batting_hands', []);
    }

    public function documentation(): array
    {
        return [
            [
                'intent' => 'rank_bowlers_phase_vs_hand',
                'description' =>
                    'Rank bowlers for a phase, optionally against left- or right-handed batters.',
                'metrics' => ['economy', 'wickets', 'wicket_rate', 'dot_ball_pct'],
                'entities' => ['bowler'],
                'example' =>
                    'Show me our best death-over bowlers against left-handed batters this season.',
            ],
            [
                'intent' => 'team_run_rate_trend',
                'description' =>
                    'Show match-by-match team run-rate trend for a phase.',
                'metrics' => ['run_rate'],
                'entities' => ['team'],
                'example' =>
                    'Why did our middle-over run rate decrease during our previous four matches?',
            ],
            [
                'intent' => 'batter_phase_performance',
                'description' =>
                    'Analyze a batter or team batters in a selected phase.',
                'metrics' => ['strike_rate', 'runs', 'dot_ball_pct', 'boundary_pct'],
                'entities' => ['batter'],
                'example' =>
                    'Show our top powerplay batters by strike rate this season.',
            ],
            [
                'intent' => 'bowler_phase_performance',
                'description' =>
                    'Analyze a bowler in a selected phase.',
                'metrics' => ['economy', 'wickets', 'wicket_rate', 'dot_ball_pct'],
                'entities' => ['bowler'],
                'example' =>
                    'How has Nimal bowled in the death overs this season?',
            ],
            [
                'intent' => 'player_form_trend',
                'description' =>
                    'Show recent innings form for one player.',
                'metrics' => ['runs', 'strike_rate', 'economy', 'wickets'],
                'entities' => ['player'],
                'example' =>
                    'Show Kamal Perera form over the last five matches.',
            ],
            [
                'intent' => 'team_phase_scoring',
                'description' =>
                    'Summarize team scoring across powerplay, middle and death phases.',
                'metrics' => ['run_rate', 'runs', 'wickets'],
                'entities' => ['team'],
                'example' =>
                    'Compare our scoring rate across phases this season.',
            ],
            [
                'intent' => 'matchup_summary',
                'description' =>
                    'Summarize a controlled batter-vs-bowler historical matchup.',
                'metrics' => ['runs', 'balls', 'strike_rate', 'dismissals'],
                'entities' => ['matchup'],
                'example' =>
                    'Show the matchup between Batter A and Bowler B.',
            ],
            [
                'intent' => 'venue_scoring_summary',
                'description' =>
                    'Summarize first-innings scoring at a venue.',
                'metrics' => ['first_innings_total'],
                'entities' => ['venue'],
                'example' =>
                    'What is the typical first-innings score at R Premadasa this season?',
            ],
            [
                'intent' => 'opponent_phase_threats',
                'description' =>
                    'Rank opposition batting threats within a phase.',
                'metrics' => ['strike_rate', 'runs', 'boundary_pct'],
                'entities' => ['batter'],
                'example' =>
                    'Who are the opposition main powerplay threats?',
            ],
        ];
    }
}
