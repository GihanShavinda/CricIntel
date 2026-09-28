<?php

namespace App\Services\Analytics;

class FilteredPlayerStatisticsService
{
    public function __construct(
        private readonly AnalyticsQuery $query
    ) {}

    public function forPlayer(
        int $organizationId,
        int $playerId,
        array $filters = []
    ): array {
        return [
            'batting' => $this->batting(
                $organizationId,
                $playerId,
                $filters
            ),
            'bowling' => $this->bowling(
                $organizationId,
                $playerId,
                $filters
            ),
            'fielding' => $this->fielding(
                $organizationId,
                $playerId,
                $filters
            ),
        ];
    }

    private function batting(
        int $organizationId,
        int $playerId,
        array $filters
    ): array {
        $base = $this->query
            ->deliveries(
                $organizationId,
                $filters
            )
            ->where(
                'd.batter_id',
                $playerId
            );

        if (
            ! empty(
                $filters['bowling_type']
            )
        ) {
            $base->join(
                'players as bp',
                'bp.id',
                '=',
                'd.bowler_id'
            );

            $typeSql =
                $this->query
                    ->bowlingTypeExpression(
                        'bp'
                    );

            $base->whereRaw(
                "{$typeSql} = ?",
                [
                    $filters[
                        'bowling_type'
                    ],
                ]
            );
        }

        if (
            ! empty(
                $filters[
                    'batting_position'
                ]
            )
        ) {
            $base->join(
                'match_players as mp',
                function ($join) {
                    $join
                        ->on(
                            'mp.match_id',
                            '=',
                            'm.id'
                        )
                        ->on(
                            'mp.player_id',
                            '=',
                            'd.batter_id'
                        );
                }
            )
                ->where(
                    'mp.batting_position',
                    (int) $filters[
                        'batting_position'
                    ]
                );
        }

        $totals = (clone $base)
            ->selectRaw('
                COUNT(DISTINCT m.id) AS matches,
                COUNT(DISTINCT i.id) AS innings,
                COALESCE(SUM(d.runs_off_bat),0) AS runs,
                COUNT(*) FILTER (WHERE d.is_legal = true) AS balls,
                COUNT(*) FILTER (WHERE d.runs_off_bat = 4) AS fours,
                COUNT(*) FILTER (WHERE d.runs_off_bat = 6) AS sixes,
                COUNT(*) FILTER (
                    WHERE d.is_legal = true
                    AND d.total_runs = 0
                ) AS dots,
                COUNT(*) FILTER (
                    WHERE d.wicket = true
                    AND d.dismissed_player_id = ?
                    AND COALESCE(d.wicket_type, \'\') <> \'retired_hurt\'
                ) AS dismissals
            ', [$playerId])
            ->first();

        $inningsScores = (clone $base)
            ->selectRaw(
                'i.id, SUM(d.runs_off_bat) AS runs'
            )
            ->groupBy('i.id')
            ->pluck('runs')
            ->map(
                static fn ($value) =>
                    (int) $value
            );

        $runs =
            (int) (
                $totals->runs ??
                0
            );

        $balls =
            (int) (
                $totals->balls ??
                0
            );

        $dismissalQuery =
            $this->query
                ->deliveries(
                    $organizationId,
                    $filters
                )
                ->where(
                    'd.wicket',
                    true
                )
                ->where(
                    'd.dismissed_player_id',
                    $playerId
                )
                ->where(
                    'd.wicket_type',
                    '<>',
                    'retired_hurt'
                );

        if (
            ! empty(
                $filters['bowling_type']
            )
        ) {
            $dismissalQuery
                ->join(
                    'players as dismissal_bp',
                    'dismissal_bp.id',
                    '=',
                    'd.bowler_id'
                );

            $dismissalTypeSql =
                $this->query
                    ->bowlingTypeExpression(
                        'dismissal_bp'
                    );

            $dismissalQuery
                ->whereRaw(
                    "{$dismissalTypeSql} = ?",
                    [
                        $filters[
                            'bowling_type'
                        ],
                    ]
                );
        }

        if (
            ! empty(
                $filters[
                    'batting_position'
                ]
            )
        ) {
            $dismissalQuery
                ->join(
                    'match_players as dismissal_mp',
                    function ($join) use ($playerId) {
                        $join
                            ->on(
                                'dismissal_mp.match_id',
                                '=',
                                'm.id'
                            )
                            ->where(
                                'dismissal_mp.player_id',
                                '=',
                                $playerId
                            );
                    }
                )
                ->where(
                    'dismissal_mp.batting_position',
                    (int) $filters[
                        'batting_position'
                    ]
                );
        }

        $dismissals =
            $dismissalQuery->count();

        $fours =
            (int) (
                $totals->fours ??
                0
            );

        $sixes =
            (int) (
                $totals->sixes ??
                0
            );

        $dots =
            (int) (
                $totals->dots ??
                0
            );

        $boundaryRuns =
            ($fours * 4) +
            ($sixes * 6);

        $phaseRates =
            $this->battingPhaseRates(
                $organizationId,
                $playerId,
                $filters
            );

        $typeRates =
            $this->battingTypeRates(
                $organizationId,
                $playerId,
                $filters
            );

        return [
            'matches' =>
                (int) (
                    $totals->matches ??
                    0
                ),

            'innings' =>
                (int) (
                    $totals->innings ??
                    0
                ),

            'runs' => $runs,

            'balls_faced' =>
                $balls,

            'highest_score' =>
                $inningsScores->max() ??
                0,

            'average' =>
                $dismissals > 0
                    ? round(
                        $runs /
                        $dismissals,
                        2
                    )
                    : null,

            'strike_rate' =>
                $balls > 0
                    ? round(
                        ($runs / $balls) *
                        100,
                        2
                    )
                    : 0.0,

            'fifties' =>
                $inningsScores
                    ->filter(
                        fn ($score) =>
                            $score >= 50 &&
                            $score < 100
                    )
                    ->count(),

            'hundreds' =>
                $inningsScores
                    ->filter(
                        fn ($score) =>
                            $score >= 100
                    )
                    ->count(),

            'fours' =>
                $fours,

            'sixes' =>
                $sixes,

            'boundary_percentage' =>
                $runs > 0
                    ? round(
                        (
                            $boundaryRuns /
                            $runs
                        ) *
                        100,
                        2
                    )
                    : 0.0,

            'dot_ball_percentage' =>
                $balls > 0
                    ? round(
                        (
                            $dots /
                            $balls
                        ) *
                        100,
                        2
                    )
                    : 0.0,

            'powerplay_strike_rate' =>
                $phaseRates[
                    'powerplay'
                ] ?? 0.0,

            'middle_over_strike_rate' =>
                $phaseRates[
                    'middle'
                ] ?? 0.0,

            'death_over_strike_rate' =>
                $phaseRates[
                    'death'
                ] ?? 0.0,

            'spin_strike_rate' =>
                $typeRates[
                    'spin'
                ] ?? 0.0,

            'pace_strike_rate' =>
                $typeRates[
                    'pace'
                ] ?? 0.0,
        ];
    }

    private function bowling(
        int $organizationId,
        int $playerId,
        array $filters
    ): array {
        $base = $this->query
            ->deliveries(
                $organizationId,
                $filters
            )
            ->where(
                'd.bowler_id',
                $playerId
            );

        $runsSql = "CASE
            WHEN d.extra_type IN (
                'bye',
                'leg_bye',
                'penalty'
            )
                THEN d.runs_off_bat
            ELSE d.total_runs
        END";

        $row = (clone $base)
            ->selectRaw("
                COUNT(*) FILTER (
                    WHERE d.is_legal = true
                ) AS balls,
                COALESCE(
                    SUM({$runsSql}),
                    0
                ) AS runs,
                COUNT(*) FILTER (
                    WHERE d.wicket = true
                    AND d.wicket_type NOT IN (
                        'run_out',
                        'retired_hurt',
                        'obstructing_field'
                    )
                ) AS wickets,
                COUNT(*) FILTER (
                    WHERE d.is_legal = true
                    AND d.total_runs = 0
                ) AS dots,
                COUNT(*) FILTER (
                    WHERE d.runs_off_bat IN (4,6)
                ) AS boundaries
            ")
            ->first();

        $balls =
            (int) (
                $row->balls ??
                0
            );

        $runs =
            (int) (
                $row->runs ??
                0
            );

        $wickets =
            (int) (
                $row->wickets ??
                0
            );

        $dots =
            (int) (
                $row->dots ??
                0
            );

        $boundaries =
            (int) (
                $row->boundaries ??
                0
            );

        $deliveries =
            (clone $base)->count();

        $phaseEconomy =
            $this->bowlingPhaseEconomy(
                $organizationId,
                $playerId,
                $filters
            );

        return [
            'overs' =>
                intdiv(
                    $balls,
                    6
                )
                . '.'
                . (
                    $balls %
                    6
                ),

            'balls' =>
                $balls,

            'runs_conceded' =>
                $runs,

            'wickets' =>
                $wickets,

            'average' =>
                $wickets > 0
                    ? round(
                        $runs /
                        $wickets,
                        2
                    )
                    : null,

            'economy' =>
                $balls > 0
                    ? round(
                        ($runs * 6) /
                        $balls,
                        2
                    )
                    : 0.0,

            'strike_rate' =>
                $wickets > 0
                    ? round(
                        $balls /
                        $wickets,
                        2
                    )
                    : null,

            'dot_percentage' =>
                $balls > 0
                    ? round(
                        (
                            $dots /
                            $balls
                        ) *
                        100,
                        2
                    )
                    : 0.0,

            'boundary_conceded_percentage' =>
                $deliveries > 0
                    ? round(
                        (
                            $boundaries /
                            $deliveries
                        ) *
                        100,
                        2
                    )
                    : 0.0,

            'maidens' =>
                $this->maidens(
                    $organizationId,
                    $playerId,
                    $filters
                ),

            'powerplay_economy' =>
                $phaseEconomy[
                    'powerplay'
                ] ?? 0.0,

            'middle_over_economy' =>
                $phaseEconomy[
                    'middle'
                ] ?? 0.0,

            'death_over_economy' =>
                $phaseEconomy[
                    'death'
                ] ?? 0.0,
        ];
    }

    private function fielding(
        int $organizationId,
        int $playerId,
        array $filters
    ): array {
        $base = $this->query
            ->deliveries(
                $organizationId,
                $filters
            );

        $dismissals = (clone $base)
            ->where(
                'd.fielder_id',
                $playerId
            )
            ->selectRaw("
                COUNT(*) FILTER (
                    WHERE d.wicket_type = 'caught'
                ) AS catches,
                COUNT(*) FILTER (
                    WHERE d.wicket_type = 'run_out'
                ) AS run_outs,
                COUNT(*) FILTER (
                    WHERE d.wicket_type = 'stumped'
                ) AS stumpings
            ")
            ->first();

        $events = (clone $base)
            ->where(
                'd.fielding_player_id',
                $playerId
            )
            ->selectRaw("
                COUNT(*) FILTER (
                    WHERE d.fielding_event_type = 'drop'
                ) AS drops,
                COUNT(*) FILTER (
                    WHERE d.fielding_event_type = 'opportunity'
                ) AS opportunities
            ")
            ->first();

        $catches =
            (int) (
                $dismissals->catches ??
                0
            );

        $runOuts =
            (int) (
                $dismissals->run_outs ??
                0
            );

        $stumpings =
            (int) (
                $dismissals->stumpings ??
                0
            );

        $drops =
            (int) (
                $events->drops ??
                0
            );

        $other =
            (int) (
                $events->opportunities ??
                0
            );

        $successful =
            $catches +
            $runOuts +
            $stumpings;

        $opportunities =
            $successful +
            $drops +
            $other;

        return [
            'catches' =>
                $catches,

            'run_outs' =>
                $runOuts,

            'stumpings' =>
                $stumpings,

            'drops' =>
                $drops,

            'fielding_opportunities' =>
                $opportunities,

            'fielding_efficiency' =>
                $opportunities > 0
                    ? round(
                        (
                            $successful /
                            $opportunities
                        ) *
                        100,
                        2
                    )
                    : null,
        ];
    }

    private function battingPhaseRates(
        int $organizationId,
        int $playerId,
        array $filters
    ): array {
        $filtersWithoutPhase =
            $filters;

        unset(
            $filtersWithoutPhase[
                'phase'
            ]
        );

        $phaseSql =
            $this->query
                ->phaseExpression();

        $rows =
            $this->query
                ->deliveries(
                    $organizationId,
                    $filtersWithoutPhase
                )
                ->where(
                    'd.batter_id',
                    $playerId
                )
                ->selectRaw("
                    {$phaseSql} AS phase,
                    SUM(d.runs_off_bat) AS runs,
                    COUNT(*) FILTER (
                        WHERE d.is_legal = true
                    ) AS balls
                ")
                ->groupByRaw(
                    $phaseSql
                )
                ->get();

        $result = [];

        foreach (
            $rows as $row
        ) {
            $balls =
                (int) $row->balls;

            $runs =
                (int) $row->runs;

            $result[
                $row->phase
            ] =
                $balls > 0
                    ? round(
                        (
                            $runs /
                            $balls
                        ) *
                        100,
                        2
                    )
                    : 0.0;
        }

        return $result;
    }

    private function battingTypeRates(
        int $organizationId,
        int $playerId,
        array $filters
    ): array {
        $filtersWithoutType =
            $filters;

        unset(
            $filtersWithoutType[
                'bowling_type'
            ]
        );

        $typeSql =
            $this->query
                ->bowlingTypeExpression(
                    'bp'
                );

        $rows =
            $this->query
                ->deliveries(
                    $organizationId,
                    $filtersWithoutType
                )
                ->join(
                    'players as bp',
                    'bp.id',
                    '=',
                    'd.bowler_id'
                )
                ->where(
                    'd.batter_id',
                    $playerId
                )
                ->selectRaw("
                    {$typeSql} AS type,
                    SUM(d.runs_off_bat) AS runs,
                    COUNT(*) FILTER (
                        WHERE d.is_legal = true
                    ) AS balls
                ")
                ->groupByRaw(
                    $typeSql
                )
                ->get();

        $result = [];

        foreach (
            $rows as $row
        ) {
            $balls =
                (int) $row->balls;

            $runs =
                (int) $row->runs;

            $result[
                $row->type
            ] =
                $balls > 0
                    ? round(
                        (
                            $runs /
                            $balls
                        ) *
                        100,
                        2
                    )
                    : 0.0;
        }

        return $result;
    }

    private function bowlingPhaseEconomy(
        int $organizationId,
        int $playerId,
        array $filters
    ): array {
        $filtersWithoutPhase =
            $filters;

        unset(
            $filtersWithoutPhase[
                'phase'
            ]
        );

        $phaseSql =
            $this->query
                ->phaseExpression();

        $runsSql = "CASE
            WHEN d.extra_type IN (
                'bye',
                'leg_bye',
                'penalty'
            )
                THEN d.runs_off_bat
            ELSE d.total_runs
        END";

        $rows =
            $this->query
                ->deliveries(
                    $organizationId,
                    $filtersWithoutPhase
                )
                ->where(
                    'd.bowler_id',
                    $playerId
                )
                ->selectRaw("
                    {$phaseSql} AS phase,
                    COUNT(*) FILTER (
                        WHERE d.is_legal = true
                    ) AS balls,
                    SUM({$runsSql}) AS runs
                ")
                ->groupByRaw(
                    $phaseSql
                )
                ->get();

        $result = [];

        foreach (
            $rows as $row
        ) {
            $balls =
                (int) $row->balls;

            $runs =
                (int) $row->runs;

            $result[
                $row->phase
            ] =
                $balls > 0
                    ? round(
                        (
                            $runs *
                            6
                        ) /
                        $balls,
                        2
                    )
                    : 0.0;
        }

        return $result;
    }

    private function maidens(
        int $organizationId,
        int $playerId,
        array $filters
    ): int {
        $base =
            $this->query
                ->deliveries(
                    $organizationId,
                    $filters
                )
                ->where(
                    'd.bowler_id',
                    $playerId
                )
                ->where(
                    'o.legal_balls',
                    6
                )
                ->where(
                    'o.status',
                    'Completed'
                );

        $runsSql = "CASE
            WHEN d.extra_type IN (
                'bye',
                'leg_bye',
                'penalty'
            )
                THEN d.runs_off_bat
            ELSE d.total_runs
        END";

        return $base
            ->selectRaw("
                o.id,
                SUM({$runsSql}) AS runs
            ")
            ->groupBy('o.id')
            ->havingRaw(
                "SUM({$runsSql}) = 0"
            )
            ->get()
            ->count();
    }
}
