<?php

namespace App\Services\Statistics;

use Illuminate\Support\Facades\DB;

class BowlingStatisticsService
{
    public function __construct(
        private readonly StatisticsQuery $queries
    ) {}

    public function forPlayer(
        int $organizationId,
        int $playerId,
        array $filters = []
    ): array {
        $runsSql =
            StatisticsQuery::bowlerRunsConcededSql('d');

        $totals = $this->queries
            ->deliveries(
                $organizationId,
                $filters
            )
            ->where(
                'd.bowler_id',
                $playerId
            )
            ->selectRaw("
                COUNT(*) FILTER (
                    WHERE d.is_legal = true
                ) AS legal_balls,

                COALESCE(
                    SUM({$runsSql}),
                    0
                ) AS runs_conceded,

                COUNT(*) FILTER (
                    WHERE
                        d.is_legal = true
                        AND d.total_runs = 0
                ) AS dot_balls,

                COUNT(*) FILTER (
                    WHERE
                        d.runs_off_bat IN (4, 6)
                ) AS boundaries_conceded
            ")
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Bowler Wickets
        |--------------------------------------------------------------------------
        |
        | Do not credit these dismissal types to the bowler:
        |
        | - run_out
        | - retired_hurt
        | - obstructing_field
        |
        */

        $wickets = $this->queries
            ->wickets(
                $organizationId,
                $filters
            )
            ->where(
                'w.bowler_id',
                $playerId
            )
            ->whereNotIn(
                'w.wicket_type',
                [
                    'run_out',
                    'retired_hurt',
                    'obstructing_field',
                ]
            )
            ->count();

        $legalBalls = (int) (
            $totals->legal_balls ?? 0
        );

        $runsConceded = (int) (
            $totals->runs_conceded ?? 0
        );

        $dots = (int) (
            $totals->dot_balls ?? 0
        );

        $boundaries = (int) (
            $totals->boundaries_conceded ?? 0
        );

        /*
        |--------------------------------------------------------------------------
        | Total Deliveries Bowled
        |--------------------------------------------------------------------------
        |
        | This includes illegal balls such as wides and no-balls.
        |
        | Used for boundary-conceded percentage.
        |
        */

        $totalDeliveries = $this->queries
            ->deliveries(
                $organizationId,
                $filters
            )
            ->where(
                'd.bowler_id',
                $playerId
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Maidens
        |--------------------------------------------------------------------------
        */

        $maidens = $this->maidens(
            $organizationId,
            $playerId,
            $filters
        );

        /*
        |--------------------------------------------------------------------------
        | Phase Economy
        |--------------------------------------------------------------------------
        */

        $phases = $this->phaseEconomy(
            $organizationId,
            $playerId,
            $filters
        );

        return [
            'overs' =>
            $this->oversNotation(
                $legalBalls
            ),

            'balls' =>
            $legalBalls,

            'runs_conceded' =>
            $runsConceded,

            'wickets' =>
            $wickets,

            'average' =>
            $wickets > 0
                ? round(
                    $runsConceded /
                        $wickets,
                    2
                )
                : null,

            'economy' =>
            $legalBalls > 0
                ? round(
                    (
                        $runsConceded *
                        6
                    ) /
                        $legalBalls,
                    2
                )
                : 0.0,

            'strike_rate' =>
            $wickets > 0
                ? round(
                    $legalBalls /
                        $wickets,
                    2
                )
                : null,

            'dot_percentage' =>
            $legalBalls > 0
                ? round(
                    (
                        $dots /
                        $legalBalls
                    ) *
                        100,
                    2
                )
                : 0.0,

            'boundary_conceded_percentage' =>
            $totalDeliveries > 0
                ? round(
                    (
                        $boundaries /
                        $totalDeliveries
                    ) *
                        100,
                    2
                )
                : 0.0,

            'maidens' =>
            $maidens,

            'powerplay_economy' =>
            $phases['powerplay']['economy']
                ?? 0.0,

            'middle_over_economy' =>
            $phases['middle']['economy']
                ?? 0.0,

            'death_over_economy' =>
            $phases['death']['economy']
                ?? 0.0,

            'phases' =>
            $phases,
        ];
    }

    /**
     * Calculate completed maiden overs.
     *
     * A maiden must:
     *
     * 1. Belong to this bowler
     * 2. Contain exactly six legal balls
     * 3. Be a completed over
     * 4. Concede zero bowler-attributable runs
     *
     * An incomplete over with zero runs is NOT a maiden.
     */
    private function maidens(
        int $organizationId,
        int $playerId,
        array $filters
    ): int {
        $runsSql =
            StatisticsQuery::bowlerRunsConcededSql(
                'd'
            );

        $query = DB::table(
            'overs as o'
        )
            ->join(
                'innings as i',
                'i.id',
                '=',
                'o.innings_id'
            )
            ->join(
                'matches as m',
                'm.id',
                '=',
                'i.match_id'
            )
            ->join(
                'deliveries as d',
                'd.over_id',
                '=',
                'o.id'
            )
            ->where(
                'm.organization_id',
                $organizationId
            )
            ->where(
                'o.bowler_id',
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

        /*
        |--------------------------------------------------------------------------
        | Optional Date Filters
        |--------------------------------------------------------------------------
        */

        if (
            !empty($filters['from'])
        ) {
            $query->whereDate(
                'd.delivery_timestamp',
                '>=',
                $filters['from']
            );
        }

        if (
            !empty($filters['to'])
        ) {
            $query->whereDate(
                'd.delivery_timestamp',
                '<=',
                $filters['to']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Calculate Runs Conceded Per Completed Over
        |--------------------------------------------------------------------------
        */

        $completedOvers =
            $query
            ->selectRaw("
                    o.id AS over_id,

                    COALESCE(
                        SUM({$runsSql}),
                        0
                    ) AS runs_conceded
                ")
            ->groupBy(
                'o.id'
            );

        /*
        |--------------------------------------------------------------------------
        | Maiden = Completed Over + Zero Runs
        |--------------------------------------------------------------------------
        */

        return DB::query()
            ->fromSub(
                $completedOvers,
                'completed_overs'
            )
            ->where(
                'completed_overs.runs_conceded',
                0
            )
            ->count();
    }

    /**
     * Calculate economy by match phase.
     *
     * Current deterministic T20 phases:
     *
     * Powerplay : overs 1-6
     * Middle    : overs 7-15
     * Death     : overs 16+
     */
    private function phaseEconomy(
        int $organizationId,
        int $playerId,
        array $filters
    ): array {
        $phaseSql =
            StatisticsQuery::phaseSql(
                'o'
            );

        $runsSql =
            StatisticsQuery::bowlerRunsConcededSql(
                'd'
            );

        $rows = $this->queries
            ->deliveries(
                $organizationId,
                $filters
            )
            ->join(
                'overs as o',
                'o.id',
                '=',
                'd.over_id'
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

                COALESCE(
                    SUM({$runsSql}),
                    0
                ) AS runs
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

            $result[$row->phase] = [
                'balls' =>
                $balls,

                'runs' =>
                $runs,

                'economy' =>
                $balls > 0
                    ? round(
                        (
                            $runs *
                            6
                        ) /
                            $balls,
                        2
                    )
                    : 0.0,
            ];
        }

        return $result;
    }

    /**
     * Convert legal balls into cricket over notation.
     *
     * Examples:
     *
     * 6 balls  = 1.0
     * 7 balls  = 1.1
     * 11 balls = 1.5
     * 12 balls = 2.0
     */
    private function oversNotation(
        int $legalBalls
    ): string {
        return
            intdiv(
                $legalBalls,
                6
            )
            . '.'
            . (
                $legalBalls %
                6
            );
    }
}
