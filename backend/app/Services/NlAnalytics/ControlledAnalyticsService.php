<?php

namespace App\Services\NlAnalytics;

use App\Services\OpponentAnalytics\MatchupQueryService;
use App\Services\OpponentAnalytics\OpponentAnalyticsCalculator;
use App\Services\OpponentAnalytics\OpponentAnalyticsService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ControlledAnalyticsService
{
    public function __construct(
        private readonly MatchupQueryService $queries,
        private readonly OpponentAnalyticsCalculator $calculator,
        private readonly OpponentAnalyticsService $opponentAnalytics
    ) {}

    public function execute(
        int $organizationId,
        array $schema
    ): array {
        return match ($schema['intent']) {
            'rank_bowlers_phase_vs_hand' =>
                $this->rankBowlers(
                    $organizationId,
                    $schema
                ),

            'team_run_rate_trend' =>
                $this->teamRunRateTrend(
                    $organizationId,
                    $schema
                ),

            'batter_phase_performance' =>
                $this->batterPhasePerformance(
                    $organizationId,
                    $schema
                ),

            'bowler_phase_performance' =>
                $this->bowlerPhasePerformance(
                    $organizationId,
                    $schema
                ),

            'player_form_trend' =>
                $this->playerFormTrend(
                    $organizationId,
                    $schema
                ),

            'team_phase_scoring' =>
                $this->teamPhaseScoring(
                    $organizationId,
                    $schema
                ),

            'matchup_summary' =>
                $this->matchupSummary(
                    $organizationId,
                    $schema
                ),

            'venue_scoring_summary' =>
                $this->venueScoringSummary(
                    $organizationId,
                    $schema
                ),

            'opponent_phase_threats' =>
                $this->opponentPhaseThreats(
                    $organizationId,
                    $schema
                ),

            default => throw new \InvalidArgumentException(
                'Unsupported controlled analytics intent.'
            ),
        };
    }

    private function rankBowlers(
        int $organizationId,
        array $schema
    ): array {
        $rows = $this->filteredDeliveries(
            $organizationId,
            $schema
        )
            ->where(
                'bowling_team_id',
                (int) $schema['team_id']
            )
            ->values();

        $rows = $this->applyPhaseAndHand(
            $rows,
            $schema
        );

        $playerNames = $this->playerNames(
            $rows->pluck('bowler_id')
        );

        $data = $rows
            ->groupBy('bowler_id')
            ->map(function (
                Collection $group,
                $bowlerId
            ) use ($playerNames) {
                $legal = $group->filter(
                    fn ($row) =>
                        $this->isLegalBall($row)
                );

                $balls = $legal->count();
                $runsConceded = (int) $group->sum(
                    fn ($row) =>
                        $this->bowlerRunsConceded($row)
                );

                $wickets = $group
                    ->filter(
                        fn ($row) =>
                            $this->calculator
                                ->normalizedDismissalWicket($row)
                    )
                    ->count();

                $dots = $legal
                    ->filter(
                        fn ($row) =>
                            (int) $row->total_runs === 0
                    )
                    ->count();

                return [
                    'player_id' => (int) $bowlerId,
                    'player_name' =>
                        $playerNames[(int) $bowlerId]
                        ?? "Player {$bowlerId}",
                    'balls' => $balls,
                    'runs_conceded' => $runsConceded,
                    'wickets' => $wickets,
                    'economy' =>
                        $this->calculator->economy(
                            $runsConceded,
                            $balls
                        ),
                    'wicket_rate' =>
                        $this->calculator->wicketRate(
                            $wickets,
                            $balls
                        ),
                    'dot_ball_pct' =>
                        $this->calculator->percentage(
                            $dots,
                            $balls
                        ),
                    'sample_size' =>
                        $this->calculator->sampleSize(
                            $balls
                        ),
                ];
            })
            ->filter(
                fn (array $row) =>
                    $row['balls'] >= 6
            )
            ->values();

        $data = $this->sortRows(
            $data,
            $schema['metric'],
            $schema['sort_direction']
        )
            ->take($schema['limit'])
            ->values();

        return $this->result(
            title:
                'Bowler ranking',
            rows:
                $data->all(),
            columns: [
                'player_name',
                'balls',
                'runs_conceded',
                'wickets',
                'economy',
                'wicket_rate',
                'dot_ball_pct',
            ],
            visualization: [
                'type' => 'bar',
                'x_key' => 'player_name',
                'y_key' => $schema['metric'],
                'label' => $this->metricLabel(
                    $schema['metric']
                ),
            ],
            source: [
                'service' =>
                    MatchupQueryService::class,
                'tables' => [
                    'deliveries',
                    'innings',
                    'matches',
                    'fixtures',
                    'overs',
                    'players',
                ],
            ],
            sample: [
                'deliveries' => $rows->count(),
                'legal_balls' => $rows
                    ->filter(
                        fn ($row) =>
                            $this->isLegalBall($row)
                    )
                    ->count(),
            ]
        );
    }

    private function teamRunRateTrend(
        int $organizationId,
        array $schema
    ): array {
        $rows = $this->filteredDeliveries(
            $organizationId,
            $schema
        )
            ->where(
                'batting_team_id',
                (int) $schema['team_id']
            )
            ->values();

        $rows = $this->applyPhaseAndHand(
            $rows,
            $schema
        );

        $grouped = $rows
            ->groupBy('match_id')
            ->map(function (
                Collection $group,
                $matchId
            ) {
                $legalBalls = $group
                    ->filter(
                        fn ($row) =>
                            $this->isLegalBall($row)
                    )
                    ->count();

                $runs = (int) $group->sum(
                    'total_runs'
                );

                $dots = $group
                    ->filter(
                        fn ($row) =>
                            $this->isLegalBall($row) &&
                            (int) $row->total_runs === 0
                    )
                    ->count();

                $boundaries = $group
                    ->whereIn(
                        'runs_off_bat',
                        [4, 6]
                    )
                    ->count();

                return [
                    'match_id' => (int) $matchId,
                    'scheduled_at' =>
                        $group->first()->scheduled_at,
                    'runs' => $runs,
                    'balls' => $legalBalls,
                    'run_rate' =>
                        $legalBalls > 0
                            ? round(
                                $runs /
                                $legalBalls *
                                6,
                                2
                            )
                            : null,
                    'dot_ball_pct' =>
                        $this->calculator->percentage(
                            $dots,
                            $legalBalls
                        ),
                    'boundaries' => $boundaries,
                    'wickets_lost' =>
                        $group
                            ->filter(
                                fn ($row) =>
                                    (bool) $row->wicket
                            )
                            ->count(),
                ];
            })
            ->sortBy('scheduled_at')
            ->values();

        if ($schema['last_n_matches']) {
            $grouped = $grouped
                ->take(-1 * (int) $schema['last_n_matches'])
                ->values();
        }

        return $this->result(
            title:
                'Team run-rate trend',
            rows:
                $grouped->all(),
            columns: [
                'match_id',
                'scheduled_at',
                'runs',
                'balls',
                'run_rate',
                'dot_ball_pct',
                'boundaries',
                'wickets_lost',
            ],
            visualization: [
                'type' => 'line',
                'x_key' => 'scheduled_at',
                'y_key' => 'run_rate',
                'label' => 'Run rate',
            ],
            source: [
                'service' =>
                    MatchupQueryService::class,
                'tables' => [
                    'deliveries',
                    'innings',
                    'matches',
                    'fixtures',
                    'overs',
                ],
            ],
            sample: [
                'matches' => $grouped->count(),
                'deliveries' => $rows->count(),
            ]
        );
    }

    private function batterPhasePerformance(
        int $organizationId,
        array $schema
    ): array {
        $rows = $this->filteredDeliveries(
            $organizationId,
            $schema
        );

        if ($schema['team_id']) {
            $rows = $rows->where(
                'batting_team_id',
                (int) $schema['team_id']
            );
        }

        if ($schema['player_id']) {
            $rows = $rows->where(
                'batter_id',
                (int) $schema['player_id']
            );
        }

        $rows = $this->applyPhaseAndHand(
            $rows->values(),
            $schema
        );

        $playerNames = $this->playerNames(
            $rows->pluck('batter_id')
        );

        $data = $rows
            ->groupBy('batter_id')
            ->map(function (
                Collection $group,
                $batterId
            ) use ($playerNames) {
                $legal = $group->filter(
                    fn ($row) =>
                        $this->isLegalBall($row)
                );

                $balls = $legal->count();
                $runs = (int) $group->sum(
                    'runs_off_bat'
                );
                $dots = $legal
                    ->filter(
                        fn ($row) =>
                            (int) $row->total_runs === 0
                    )
                    ->count();
                $boundaries = $group
                    ->whereIn(
                        'runs_off_bat',
                        [4, 6]
                    )
                    ->count();

                return [
                    'player_id' => (int) $batterId,
                    'player_name' =>
                        $playerNames[(int) $batterId]
                        ?? "Player {$batterId}",
                    'runs' => $runs,
                    'balls' => $balls,
                    'strike_rate' =>
                        $this->calculator->strikeRate(
                            $runs,
                            $balls
                        ),
                    'dot_ball_pct' =>
                        $this->calculator->percentage(
                            $dots,
                            $balls
                        ),
                    'boundaries' => $boundaries,
                    'boundary_pct' =>
                        $this->calculator->percentage(
                            $boundaries,
                            $balls
                        ),
                    'sample_size' =>
                        $this->calculator->sampleSize(
                            $balls
                        ),
                ];
            })
            ->filter(
                fn (array $row) =>
                    $row['balls'] >= 6
            )
            ->values();

        $data = $this->sortRows(
            $data,
            $schema['metric'],
            $schema['sort_direction']
        )
            ->take($schema['limit'])
            ->values();

        return $this->result(
            title:
                'Batter phase performance',
            rows:
                $data->all(),
            columns: [
                'player_name',
                'runs',
                'balls',
                'strike_rate',
                'dot_ball_pct',
                'boundaries',
                'boundary_pct',
            ],
            visualization: [
                'type' => 'bar',
                'x_key' => 'player_name',
                'y_key' => $schema['metric'],
                'label' => $this->metricLabel(
                    $schema['metric']
                ),
            ],
            source: [
                'service' =>
                    MatchupQueryService::class,
            ],
            sample: [
                'deliveries' => $rows->count(),
            ]
        );
    }

    private function bowlerPhasePerformance(
        int $organizationId,
        array $schema
    ): array {
        $copy = $schema;

        if (
            ! $copy['team_id'] &&
            $copy['player_id']
        ) {
            $copy['team_id'] = null;
        }

        $rows = $this->filteredDeliveries(
            $organizationId,
            $copy
        );

        if ($copy['team_id']) {
            $rows = $rows->where(
                'bowling_team_id',
                (int) $copy['team_id']
            );
        }

        if ($copy['player_id']) {
            $rows = $rows->where(
                'bowler_id',
                (int) $copy['player_id']
            );
        }

        $rows = $this->applyPhaseAndHand(
            $rows->values(),
            $copy
        );

        $copy['team_id'] = $copy['team_id'] ?: -1;

        $playerNames = $this->playerNames(
            $rows->pluck('bowler_id')
        );

        $data = $rows
            ->groupBy('bowler_id')
            ->map(function (
                Collection $group,
                $bowlerId
            ) use ($playerNames) {
                $legal = $group->filter(
                    fn ($row) =>
                        $this->isLegalBall($row)
                );
                $balls = $legal->count();
                $runs = (int) $group->sum(
                    fn ($row) =>
                        $this->bowlerRunsConceded($row)
                );
                $wickets = $group
                    ->filter(
                        fn ($row) =>
                            $this->calculator
                                ->normalizedDismissalWicket($row)
                    )
                    ->count();
                $dots = $legal
                    ->filter(
                        fn ($row) =>
                            (int) $row->total_runs === 0
                    )
                    ->count();

                return [
                    'player_id' => (int) $bowlerId,
                    'player_name' =>
                        $playerNames[(int) $bowlerId]
                        ?? "Player {$bowlerId}",
                    'balls' => $balls,
                    'runs_conceded' => $runs,
                    'wickets' => $wickets,
                    'economy' =>
                        $this->calculator->economy(
                            $runs,
                            $balls
                        ),
                    'wicket_rate' =>
                        $this->calculator->wicketRate(
                            $wickets,
                            $balls
                        ),
                    'dot_ball_pct' =>
                        $this->calculator->percentage(
                            $dots,
                            $balls
                        ),
                    'sample_size' =>
                        $this->calculator->sampleSize(
                            $balls
                        ),
                ];
            })
            ->filter(
                fn (array $row) =>
                    $row['balls'] >= 6
            )
            ->values();

        $data = $this->sortRows(
            $data,
            $schema['metric'],
            $schema['sort_direction']
        )
            ->take($schema['limit'])
            ->values();

        return $this->result(
            title:
                'Bowler phase performance',
            rows:
                $data->all(),
            columns: [
                'player_name',
                'balls',
                'runs_conceded',
                'wickets',
                'economy',
                'wicket_rate',
                'dot_ball_pct',
            ],
            visualization: [
                'type' => 'bar',
                'x_key' => 'player_name',
                'y_key' => $schema['metric'],
                'label' => $this->metricLabel(
                    $schema['metric']
                ),
            ],
            source: [
                'service' =>
                    MatchupQueryService::class,
            ],
            sample: [
                'deliveries' => $rows->count(),
            ]
        );
    }

    private function playerFormTrend(
        int $organizationId,
        array $schema
    ): array {
        $rows = $this->filteredDeliveries(
            $organizationId,
            $schema
        );

        $playerId = (int) $schema['player_id'];
        $metric = $schema['metric'];

        if (
            in_array(
                $metric,
                ['economy', 'wickets'],
                true
            )
        ) {
            $rows = $rows
                ->where(
                    'bowler_id',
                    $playerId
                )
                ->values();

            $data = $rows
                ->groupBy('match_id')
                ->map(function (
                    Collection $group,
                    $matchId
                ) {
                    $legal = $group->filter(
                        fn ($row) =>
                            $this->isLegalBall($row)
                    );

                    $balls = $legal->count();
                    $runs = (int) $group->sum(
                        fn ($row) =>
                            $this->bowlerRunsConceded($row)
                    );

                    $wickets = $group
                        ->filter(
                            fn ($row) =>
                                $this->calculator
                                    ->normalizedDismissalWicket($row)
                        )
                        ->count();

                    return [
                        'match_id' => (int) $matchId,
                        'scheduled_at' =>
                            $group->first()->scheduled_at,
                        'balls' => $balls,
                        'runs_conceded' => $runs,
                        'wickets' => $wickets,
                        'economy' =>
                            $this->calculator->economy(
                                $runs,
                                $balls
                            ),
                    ];
                })
                ->sortBy('scheduled_at')
                ->values();
        } else {
            $rows = $rows
                ->where(
                    'batter_id',
                    $playerId
                )
                ->values();

            $data = $rows
                ->groupBy('match_id')
                ->map(function (
                    Collection $group,
                    $matchId
                ) {
                    $legal = $group->filter(
                        fn ($row) =>
                            $this->isLegalBall($row)
                    );

                    $balls = $legal->count();
                    $runs = (int) $group->sum(
                        'runs_off_bat'
                    );

                    return [
                        'match_id' => (int) $matchId,
                        'scheduled_at' =>
                            $group->first()->scheduled_at,
                        'runs' => $runs,
                        'balls' => $balls,
                        'strike_rate' =>
                            $this->calculator->strikeRate(
                                $runs,
                                $balls
                            ),
                    ];
                })
                ->sortBy('scheduled_at')
                ->values();
        }

        if ($schema['last_n_matches']) {
            $data = $data
                ->take(
                    -1 * (int) $schema['last_n_matches']
                )
                ->values();
        }

        return $this->result(
            title:
                'Player form trend',
            rows:
                $data->all(),
            columns:
                array_keys(
                    $data->first() ?? [
                        'match_id' => null,
                        'scheduled_at' => null,
                        $metric => null,
                    ]
                ),
            visualization: [
                'type' => 'line',
                'x_key' => 'scheduled_at',
                'y_key' => $metric,
                'label' => $this->metricLabel(
                    $metric
                ),
            ],
            source: [
                'service' =>
                    MatchupQueryService::class,
            ],
            sample: [
                'matches' => $data->count(),
                'deliveries' => $rows->count(),
            ]
        );
    }

    private function teamPhaseScoring(
        int $organizationId,
        array $schema
    ): array {
        $rows = $this->filteredDeliveries(
            $organizationId,
            $schema
        )
            ->where(
                'batting_team_id',
                (int) $schema['team_id']
            )
            ->values();

        $data = $rows
            ->groupBy(
                fn ($row) =>
                    $this->phase(
                        (int) ($row->over_number ?? 0),
                        (int) ($row->max_overs ?? 20)
                    )
            )
            ->map(function (
                Collection $group,
                string $phase
            ) {
                $legal = $group->filter(
                    fn ($row) =>
                        $this->isLegalBall($row)
                );

                $balls = $legal->count();
                $runs = (int) $group->sum(
                    'total_runs'
                );

                return [
                    'phase' => $phase,
                    'runs' => $runs,
                    'balls' => $balls,
                    'run_rate' =>
                        $balls > 0
                            ? round(
                                $runs /
                                $balls *
                                6,
                                2
                            )
                            : null,
                    'wickets' =>
                        $group
                            ->filter(
                                fn ($row) =>
                                    (bool) $row->wicket
                            )
                            ->count(),
                ];
            })
            ->sortBy(
                fn ($row) =>
                    match ($row['phase']) {
                        'powerplay' => 1,
                        'middle' => 2,
                        'death' => 3,
                        default => 4,
                    }
            )
            ->values();

        return $this->result(
            title:
                'Team phase scoring',
            rows:
                $data->all(),
            columns: [
                'phase',
                'runs',
                'balls',
                'run_rate',
                'wickets',
            ],
            visualization: [
                'type' => 'bar',
                'x_key' => 'phase',
                'y_key' => $schema['metric'],
                'label' => $this->metricLabel(
                    $schema['metric']
                ),
            ],
            source: [
                'service' =>
                    MatchupQueryService::class,
            ],
            sample: [
                'deliveries' => $rows->count(),
            ]
        );
    }

    private function matchupSummary(
        int $organizationId,
        array $schema
    ): array {
        $filters = [
            'from' => $schema['date_from'],
            'to' => $schema['date_to'],
        ];

        if ($schema['opponent_team_id']) {
            $filters['opponent_team_id'] =
                $schema['opponent_team_id'];
        }

        $profile = $this->opponentAnalytics->matchup(
            $organizationId,
            (int) $schema['batter_id'],
            (int) $schema['bowler_id'],
            array_filter(
                $filters,
                fn ($value) =>
                    $value !== null &&
                    $value !== ''
            )
        );

        $row = [
            'batter_name' =>
                data_get(
                    $profile,
                    'batter.name'
                ),
            'bowler_name' =>
                data_get(
                    $profile,
                    'bowler.name'
                ),
            'runs' =>
                data_get(
                    $profile,
                    'runs'
                ),
            'balls' =>
                data_get(
                    $profile,
                    'balls'
                ),
            'strike_rate' =>
                data_get(
                    $profile,
                    'strike_rate'
                ),
            'dismissals' =>
                data_get(
                    $profile,
                    'dismissals'
                ),
            'boundaries' =>
                data_get(
                    $profile,
                    'boundaries'
                ),
            'dots' =>
                data_get(
                    $profile,
                    'dots'
                ),
            'sample_size' =>
                data_get(
                    $profile,
                    'sample_size'
                ),
        ];

        return $this->result(
            title:
                'Batter vs bowler matchup',
            rows: [$row],
            columns: array_keys($row),
            visualization: [
                'type' => 'table',
            ],
            source: [
                'service' =>
                    OpponentAnalyticsService::class,
                'method' => 'matchup',
            ],
            sample: [
                'balls' =>
                    (int) ($row['balls'] ?? 0),
            ]
        );
    }

    private function venueScoringSummary(
        int $organizationId,
        array $schema
    ): array {
        $query = DB::table('innings as i')
            ->join(
                'matches as m',
                'm.id',
                '=',
                'i.match_id'
            )
            ->join(
                'fixtures as f',
                'f.id',
                '=',
                'm.fixture_id'
            )
            ->leftJoin(
                'tournaments as t',
                't.id',
                '=',
                'f.tournament_id'
            )
            ->where(
                'm.organization_id',
                $organizationId
            )
            ->where(
                'f.venue_id',
                (int) $schema['venue_id']
            )
            ->where(
                'i.innings_number',
                1
            )
            ->whereRaw(
                "LOWER(COALESCE(i.status,'')) = 'completed'"
            );

        $this->applyFixtureFiltersToBuilder(
            $query,
            $schema
        );

        $rows = $query
            ->orderBy('f.scheduled_at')
            ->get([
                'm.id as match_id',
                'f.scheduled_at',
                'i.runs',
                'i.wickets',
                'i.overs',
            ]);

        $average = $rows->isNotEmpty()
            ? round(
                (float) $rows->avg('runs'),
                2
            )
            : null;

        $summary = [
            'venue_id' =>
                (int) $schema['venue_id'],
            'venue_name' =>
                $schema['venue_name'],
            'matches' => $rows->count(),
            'average_first_innings_total' =>
                $average,
            'minimum_first_innings_total' =>
                $rows->isNotEmpty()
                    ? (int) $rows->min('runs')
                    : null,
            'maximum_first_innings_total' =>
                $rows->isNotEmpty()
                    ? (int) $rows->max('runs')
                    : null,
        ];

        return $this->result(
            title:
                'Venue first-innings scoring summary',
            rows: [$summary],
            columns: array_keys($summary),
            visualization: [
                'type' => 'bar',
                'x_key' => 'venue_name',
                'y_key' =>
                    'average_first_innings_total',
                'label' =>
                    'Average first-innings total',
            ],
            source: [
                'service' =>
                    self::class,
                'tables' => [
                    'innings',
                    'matches',
                    'fixtures',
                    'tournaments',
                ],
                'controlled_query' => true,
            ],
            sample: [
                'matches' => $rows->count(),
            ]
        );
    }

    private function opponentPhaseThreats(
        int $organizationId,
        array $schema
    ): array {
        $copy = $schema;
        $copy['team_id'] =
            $schema['opponent_team_id'];

        $rows = $this->filteredDeliveries(
            $organizationId,
            $copy
        )
            ->where(
                'batting_team_id',
                (int) $schema['opponent_team_id']
            )
            ->values();

        $rows = $this->applyPhaseAndHand(
            $rows,
            $schema
        );

        $playerNames = $this->playerNames(
            $rows->pluck('batter_id')
        );

        $data = $rows
            ->groupBy('batter_id')
            ->map(function (
                Collection $group,
                $batterId
            ) use ($playerNames) {
                $legal = $group->filter(
                    fn ($row) =>
                        $this->isLegalBall($row)
                );

                $balls = $legal->count();
                $runs = (int) $group->sum(
                    'runs_off_bat'
                );
                $boundaries = $group
                    ->whereIn(
                        'runs_off_bat',
                        [4, 6]
                    )
                    ->count();

                return [
                    'player_id' => (int) $batterId,
                    'player_name' =>
                        $playerNames[(int) $batterId]
                        ?? "Player {$batterId}",
                    'runs' => $runs,
                    'balls' => $balls,
                    'strike_rate' =>
                        $this->calculator->strikeRate(
                            $runs,
                            $balls
                        ),
                    'boundaries' => $boundaries,
                    'boundary_pct' =>
                        $this->calculator->percentage(
                            $boundaries,
                            $balls
                        ),
                    'sample_size' =>
                        $this->calculator->sampleSize(
                            $balls
                        ),
                ];
            })
            ->filter(
                fn (array $row) =>
                    $row['balls'] >= 6
            )
            ->values();

        $data = $this->sortRows(
            $data,
            $schema['metric'],
            $schema['sort_direction']
        )
            ->take($schema['limit'])
            ->values();

        return $this->result(
            title:
                'Opponent phase threats',
            rows:
                $data->all(),
            columns: [
                'player_name',
                'runs',
                'balls',
                'strike_rate',
                'boundaries',
                'boundary_pct',
            ],
            visualization: [
                'type' => 'bar',
                'x_key' => 'player_name',
                'y_key' => $schema['metric'],
                'label' => $this->metricLabel(
                    $schema['metric']
                ),
            ],
            source: [
                'service' =>
                    MatchupQueryService::class,
            ],
            sample: [
                'deliveries' => $rows->count(),
            ]
        );
    }

    private function filteredDeliveries(
        int $organizationId,
        array $schema
    ): Collection {
        $builder = $this->queries->deliveries(
            organizationId: $organizationId,
            playerId: $schema['player_id'] ?? null,
            opponentTeamId:
                $schema['opponent_team_id'] ?? null,
            from: $schema['date_from'] ?? null,
            to: $schema['date_to'] ?? null
        );

        if ($schema['venue_id']) {
            $builder->where(
                'f.venue_id',
                $schema['venue_id']
            );
        }

        $tournamentIds =
            $this->tournamentIdsForFilters(
                $organizationId,
                $schema
            );

        if ($tournamentIds !== null) {
            if ($tournamentIds->isEmpty()) {
                return collect();
            }

            $builder->whereIn(
                'f.tournament_id',
                $tournamentIds
            );
        }

        if ($schema['last_n_matches']) {
            $matchIds =
                $this->recentMatchIds(
                    $organizationId,
                    $schema,
                    (int) $schema['last_n_matches']
                );

            if ($matchIds->isEmpty()) {
                return collect();
            }

            $builder->whereIn(
                'm.id',
                $matchIds
            );
        }

        return $builder->get();
    }

    private function applyPhaseAndHand(
        Collection $rows,
        array $schema
    ): Collection {
        if (
            ($schema['phase'] ?? 'all') !==
            'all'
        ) {
            $rows = $rows->filter(
                fn ($row) =>
                    $this->phase(
                        (int) ($row->over_number ?? 0),
                        (int) ($row->max_overs ?? 20)
                    ) === $schema['phase']
            );
        }

        if (
            ($schema['batting_hand'] ?? 'all') !==
            'all'
        ) {
            $rows = $rows->filter(
                fn ($row) =>
                    $this->calculator->battingHand(
                        $row->batter_batting_style ?? null
                    ) === $schema['batting_hand']
            );
        }

        return $rows->values();
    }

    private function tournamentIdsForFilters(
        int $organizationId,
        array $schema
    ): ?Collection {
        if (
            ! $schema['season_id'] &&
            ! $schema['format']
        ) {
            return null;
        }

        $query = DB::table('tournaments')
            ->where(
                'organization_id',
                $organizationId
            );

        if ($schema['season_id']) {
            $query->where(
                'season_id',
                $schema['season_id']
            );
        }

        if ($schema['format']) {
            $normalized =
                mb_strtolower(
                    (string) $schema['format']
                );

            $query->whereRaw(
                'LOWER(format) = ?',
                [$normalized]
            );
        }

        return $query
            ->pluck('id')
            ->map(
                fn ($id) => (int) $id
            );
    }

    private function recentMatchIds(
        int $organizationId,
        array $schema,
        int $limit
    ): Collection {
        $query = DB::table('matches as m')
            ->join(
                'fixtures as f',
                'f.id',
                '=',
                'm.fixture_id'
            )
            ->where(
                'm.organization_id',
                $organizationId
            );

        if ($schema['date_from']) {
            $query->whereDate(
                'f.scheduled_at',
                '>=',
                $schema['date_from']
            );
        }

        if ($schema['date_to']) {
            $query->whereDate(
                'f.scheduled_at',
                '<=',
                $schema['date_to']
            );
        }

        if ($schema['venue_id']) {
            $query->where(
                'f.venue_id',
                $schema['venue_id']
            );
        }

        $tournamentIds =
            $this->tournamentIdsForFilters(
                $organizationId,
                $schema
            );

        if ($tournamentIds !== null) {
            if ($tournamentIds->isEmpty()) {
                return collect();
            }

            $query->whereIn(
                'f.tournament_id',
                $tournamentIds
            );
        }

        return $query
            ->orderByDesc('f.scheduled_at')
            ->limit($limit)
            ->pluck('m.id')
            ->map(
                fn ($id) => (int) $id
            );
    }

    private function applyFixtureFiltersToBuilder(
        $query,
        array $schema
    ): void {
        if ($schema['date_from']) {
            $query->whereDate(
                'f.scheduled_at',
                '>=',
                $schema['date_from']
            );
        }

        if ($schema['date_to']) {
            $query->whereDate(
                'f.scheduled_at',
                '<=',
                $schema['date_to']
            );
        }

        if ($schema['season_id']) {
            $query->where(
                't.season_id',
                $schema['season_id']
            );
        }

        if ($schema['format']) {
            $query->whereRaw(
                'LOWER(t.format) = ?',
                [
                    mb_strtolower(
                        (string) $schema['format']
                    ),
                ]
            );
        }
    }

    private function playerNames(
        Collection $ids
    ): array {
        return DB::table('players')
            ->whereIn(
                'id',
                $ids
                    ->filter()
                    ->unique()
                    ->values()
            )
            ->pluck(
                'display_name',
                'id'
            )
            ->mapWithKeys(
                fn ($name, $id) => [
                    (int) $id => $name,
                ]
            )
            ->all();
    }

    private function sortRows(
        Collection $rows,
        string $metric,
        string $direction
    ): Collection {
        $filtered = $rows->filter(
            fn (array $row) =>
                array_key_exists(
                    $metric,
                    $row
                ) &&
                $row[$metric] !== null
        );

        return $direction === 'asc'
            ? $filtered->sortBy($metric)
            : $filtered->sortByDesc($metric);
    }

    private function isLegalBall(
        object $row
    ): bool {
        return ! in_array(
            mb_strtolower(
                (string) ($row->extra_type ?? '')
            ),
            [
                'wide',
                'wides',
                'no_ball',
                'no ball',
                'noball',
            ],
            true
        );
    }

    private function bowlerRunsConceded(
        object $row
    ): int {
        $extraType = mb_strtolower(
            (string) ($row->extra_type ?? '')
        );

        if (
            in_array(
                $extraType,
                [
                    'bye',
                    'byes',
                    'leg_bye',
                    'leg bye',
                    'leg_byes',
                    'leg byes',
                ],
                true
            )
        ) {
            return (int) $row->runs_off_bat;
        }

        return (int) $row->runs_off_bat +
            (int) $row->extra_runs;
    }

    private function phase(
        int $overNumber,
        int $maxOvers
    ): string {
        return $this->calculator->phaseForOver(
            $overNumber,
            $maxOvers
        );
    }

    private function metricLabel(
        string $metric
    ): string {
        return match ($metric) {
            'economy' => 'Economy',
            'wickets' => 'Wickets',
            'wicket_rate' => 'Wicket rate',
            'strike_rate' => 'Strike rate',
            'run_rate' => 'Run rate',
            'runs' => 'Runs',
            'average' => 'Average',
            'balls' => 'Balls',
            'dot_ball_pct' => 'Dot-ball %',
            'boundary_pct' => 'Boundary %',
            'dismissals' => 'Dismissals',
            'first_innings_total' =>
                'First-innings total',
            default => str_replace(
                '_',
                ' ',
                ucfirst($metric)
            ),
        };
    }

    private function result(
        string $title,
        array $rows,
        array $columns,
        array $visualization,
        array $source,
        array $sample
    ): array {
        return [
            'title' => $title,
            'columns' => $columns,
            'rows' => $rows,
            'visualization' => $visualization,
            'sample' => $sample,
            'source' => [
                ...$source,
                'arbitrary_sql' => false,
            ],
        ];
    }
}
