<?php

namespace App\Services\NlAnalytics;

use Illuminate\Validation\ValidationException;

class NaturalLanguageIntentParser
{
    public function __construct(
        private readonly ControlledAnalyticsSchema $schema,
        private readonly PromptInjectionGuard $guard
    ) {}

    public function parse(string $naturalLanguage): array
    {
        $this->guard->assertSafe($naturalLanguage);

        $query = trim($naturalLanguage);
        $normalized = mb_strtolower($query);

        $result = $this->schema->defaults();

        $result['intent'] = $this->detectIntent(
            $normalized
        );

        $result['metric'] = $this->detectMetric(
            $normalized,
            $result['intent']
        );

        $result['entity'] = $this->detectEntity(
            $normalized,
            $result['intent']
        );

        $result['phase'] = $this->detectPhase(
            $normalized
        );

        $result['batting_hand'] =
            $this->detectBattingHand(
                $normalized
            );

        $result['format'] =
            $this->detectFormat(
                $normalized
            );

        $result['last_n_matches'] =
            $this->detectLastNMatches(
                $normalized
            );

        $result['limit'] =
            $this->detectLimit(
                $normalized
            );

        $result['sort_direction'] =
            $this->detectSortDirection(
                $normalized,
                $result['metric'],
                $result['intent']
            );

        if (
            str_contains(
                $normalized,
                'this season'
            ) ||
            str_contains(
                $normalized,
                'current season'
            )
        ) {
            $result['season_name'] =
                '__CURRENT__';
        }

        return $result;
    }

    private function detectIntent(
        string $query
    ): string {
        /*
        |--------------------------------------------------------------------------
        | Rank bowlers by phase / batter handedness
        |--------------------------------------------------------------------------
        |
        | Example:
        | "Show me our best death-over bowlers against left-handed batters."
        |
        */
        if (
            preg_match(
                '/\b(best|top|rank|ranking)\b.*\bbowler/i',
                $query
            ) &&
            preg_match(
                '/\b(powerplay|power play|middle|death)\b/i',
                $query
            )
        ) {
            return 'rank_bowlers_phase_vs_hand';
        }

        /*
        |--------------------------------------------------------------------------
        | Team run-rate trend
        |--------------------------------------------------------------------------
        |
        | Example:
        | "Why did our middle-over run rate decrease during our previous
        | four matches?"
        |
        */
        if (
            str_contains(
                $query,
                'run rate'
            ) &&
            preg_match(
                '/\b(previous|last|recent|trend|decrease|decreased|increase|increased|why)\b/i',
                $query
            )
        ) {
            return 'team_run_rate_trend';
        }

        /*
        |--------------------------------------------------------------------------
        | Batter vs bowler matchup
        |--------------------------------------------------------------------------
        */
        if (
            preg_match(
                '/\b(matchup|match-up|head to head|head-to-head)\b/i',
                $query
            )
        ) {
            return 'matchup_summary';
        }

        /*
        |--------------------------------------------------------------------------
        | Venue scoring
        |--------------------------------------------------------------------------
        */
        if (
            preg_match(
                '/\b(venue|ground|stadium)\b/i',
                $query
            ) &&
            preg_match(
                '/\b(score|total|first innings|first-innings)\b/i',
                $query
            )
        ) {
            return 'venue_scoring_summary';
        }

        /*
        |--------------------------------------------------------------------------
        | Opponent phase threats
        |--------------------------------------------------------------------------
        |
        | Supports:
        | - opposition threat
        | - opposition threats
        | - opponent threat
        | - opponents threats
        | - dangerous opponent
        | - top opposition batters
        |
        */
        if (
            preg_match(
                '/\b(opposition|opponents?|their)\b/i',
                $query
            ) &&
            preg_match(
                '/\b(threats?|dangers?|dangerous|best|top)\b/i',
                $query
            )
        ) {
            return 'opponent_phase_threats';
        }

        /*
        |--------------------------------------------------------------------------
        | Player form trend
        |--------------------------------------------------------------------------
        */
        if (
            preg_match(
                '/\b(form|recent form|last \d+ matches)\b/i',
                $query
            ) &&
            preg_match(
                '/\b(player|batter|bowler|runs|wickets|economy|strike rate)\b/i',
                $query
            )
        ) {
            return 'player_form_trend';
        }

        /*
        |--------------------------------------------------------------------------
        | Batter phase performance
        |--------------------------------------------------------------------------
        */
        if (
            preg_match(
                '/\b(batter|batters|batsman|batsmen|batting)\b/i',
                $query
            ) &&
            preg_match(
                '/\b(powerplay|power play|middle|death)\b/i',
                $query
            )
        ) {
            return 'batter_phase_performance';
        }

        /*
        |--------------------------------------------------------------------------
        | Bowler phase performance
        |--------------------------------------------------------------------------
        */
        if (
            preg_match(
                '/\b(bowler|bowlers|bowling)\b/i',
                $query
            ) &&
            preg_match(
                '/\b(powerplay|power play|middle|death)\b/i',
                $query
            )
        ) {
            return 'bowler_phase_performance';
        }

        /*
        |--------------------------------------------------------------------------
        | Team phase scoring
        |--------------------------------------------------------------------------
        */
        if (
            preg_match(
                '/\b(compare|comparison|across)\b/i',
                $query
            ) &&
            preg_match(
                '/\b(phase|powerplay|power play|middle|death)\b/i',
                $query
            )
        ) {
            return 'team_phase_scoring';
        }

        /*
        |--------------------------------------------------------------------------
        | Unsupported analytics request
        |--------------------------------------------------------------------------
        |
        | Natural language is deliberately restricted to supported analytics
        | intents. Unsupported questions must not become arbitrary SQL.
        |
        */
        throw ValidationException::withMessages([
            'query' =>
                'CricIntel could not map this request to a supported analytics intent. ' .
                'Use one of the supported examples shown in the analytics workspace.',
        ]);
    }

    private function detectMetric(
        string $query,
        string $intent
    ): string {
        $map = [
            'economy' => [
                'economy',
                'economical',
                'runs conceded',
            ],

            'wickets' => [
                'wicket',
                'wickets',
            ],

            'wicket_rate' => [
                'wicket rate',
            ],

            'strike_rate' => [
                'strike rate',
                'strike-rate',
                'scoring rate',
            ],

            'run_rate' => [
                'run rate',
                'run-rate',
            ],

            'runs' => [
                'runs',
                'run scoring',
            ],

            'average' => [
                'average',
            ],

            'dot_ball_pct' => [
                'dot ball',
                'dot-ball',
            ],

            'boundary_pct' => [
                'boundary',
                'boundaries',
            ],

            'dismissals' => [
                'dismissal',
                'dismissals',
            ],

            'first_innings_total' => [
                'first innings',
                'first-innings',
                'team total',
                'score at',
                'typical score',
            ],
        ];

        foreach (
            $map as
            $metric => $phrases
        ) {
            foreach (
                $phrases as $phrase
            ) {
                if (
                    str_contains(
                        $query,
                        $phrase
                    )
                ) {
                    return $metric;
                }
            }
        }

        return match ($intent) {
            'rank_bowlers_phase_vs_hand',
            'bowler_phase_performance'
                => 'economy',

            'team_run_rate_trend',
            'team_phase_scoring'
                => 'run_rate',

            'batter_phase_performance',
            'opponent_phase_threats'
                => 'strike_rate',

            'player_form_trend'
                => 'runs',

            'matchup_summary'
                => 'strike_rate',

            'venue_scoring_summary'
                => 'first_innings_total',

            default
                => 'runs',
        };
    }

    private function detectEntity(
        string $query,
        string $intent
    ): string {
        return match ($intent) {
            'rank_bowlers_phase_vs_hand',
            'bowler_phase_performance'
                => 'bowler',

            'batter_phase_performance',
            'opponent_phase_threats'
                => 'batter',

            'team_run_rate_trend',
            'team_phase_scoring'
                => 'team',

            'matchup_summary'
                => 'matchup',

            'venue_scoring_summary'
                => 'venue',

            'player_form_trend'
                => 'player',

            default =>
                preg_match(
                    '/\bbowler/i',
                    $query
                )
                    ? 'bowler'
                    : 'player',
        };
    }

    private function detectPhase(
        string $query
    ): string {
        if (
            preg_match(
                '/\b(powerplay|power play)\b/i',
                $query
            )
        ) {
            return 'powerplay';
        }

        if (
            preg_match(
                '/\bmiddle(?:[- ]over)?s?\b/i',
                $query
            )
        ) {
            return 'middle';
        }

        if (
            preg_match(
                '/\bdeath(?:[- ]over)?s?\b/i',
                $query
            )
        ) {
            return 'death';
        }

        return 'all';
    }

    private function detectBattingHand(
        string $query
    ): string {
        if (
            preg_match(
                '/\bleft[- ]hand(?:ed)?\b/i',
                $query
            ) ||
            str_contains(
                $query,
                'lefties'
            )
        ) {
            return 'left';
        }

        if (
            preg_match(
                '/\bright[- ]hand(?:ed)?\b/i',
                $query
            ) ||
            str_contains(
                $query,
                'righties'
            )
        ) {
            return 'right';
        }

        return 'all';
    }

    private function detectFormat(
        string $query
    ): ?string {
        if (
            preg_match(
                '/\bt20\b|\bt20i\b|twenty20/i',
                $query
            )
        ) {
            return 'T20';
        }

        if (
            preg_match(
                '/\bodi\b|one day/i',
                $query
            )
        ) {
            return 'ODI';
        }

        if (
            preg_match(
                '/\btest\b/i',
                $query
            )
        ) {
            return 'Test';
        }

        if (
            preg_match(
                '/\bt10\b/i',
                $query
            )
        ) {
            return 'T10';
        }

        return null;
    }

    private function detectLastNMatches(
        string $query
    ): ?int {
        /*
        |--------------------------------------------------------------------------
        | Numeric form
        |--------------------------------------------------------------------------
        |
        | Examples:
        | last 4 matches
        | previous 5 matches
        | recent 3 matches
        |
        */
        if (
            preg_match(
                '/\b(?:last|previous|recent)\s+(\d+)\s+matches?\b/i',
                $query,
                $matches
            )
        ) {
            return max(
                1,
                min(
                    20,
                    (int) $matches[1]
                )
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Word form
        |--------------------------------------------------------------------------
        |
        | Examples:
        | previous four matches
        | last five matches
        |
        */
        $words = [
            'one' => 1,
            'two' => 2,
            'three' => 3,
            'four' => 4,
            'five' => 5,
            'six' => 6,
            'seven' => 7,
            'eight' => 8,
            'nine' => 9,
            'ten' => 10,
        ];

        foreach (
            $words as
            $word => $number
        ) {
            if (
                preg_match(
                    '/\b(?:last|previous|recent)\s+' .
                        preg_quote(
                            $word,
                            '/'
                        ) .
                        '\s+matches?\b/i',
                    $query
                )
            ) {
                return $number;
            }
        }

        return null;
    }

    private function detectLimit(
        string $query
    ): int {
        if (
            preg_match(
                '/\b(?:top|best)\s+(\d+)\b/i',
                $query,
                $matches
            )
        ) {
            return max(
                1,
                min(
                    config(
                        'nl_analytics.max_rows',
                        50
                    ),
                    (int) $matches[1]
                )
            );
        }

        return 10;
    }

    private function detectSortDirection(
        string $query,
        string $metric,
        string $intent
    ): string {
        if (
            preg_match(
                '/\b(lowest|least|smallest)\b/i',
                $query
            )
        ) {
            return 'asc';
        }

        if (
            preg_match(
                '/\b(highest|most|top)\b/i',
                $query
            )
        ) {
            return 'desc';
        }

        /*
        |--------------------------------------------------------------------------
        | Economy ranking
        |--------------------------------------------------------------------------
        |
        | Lower economy is normally better when asking for the best bowlers.
        |
        */
        if (
            $metric === 'economy' &&
            in_array(
                $intent,
                [
                    'rank_bowlers_phase_vs_hand',
                    'bowler_phase_performance',
                ],
                true
            )
        ) {
            return 'asc';
        }

        return 'desc';
    }
}
