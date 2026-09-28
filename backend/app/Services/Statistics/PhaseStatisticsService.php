<?php

namespace App\Services\Statistics;

use Illuminate\Support\Facades\DB;

class PhaseStatisticsService
{
    public function __construct(
        private readonly StatisticsCacheService $cache
    ) {}

    public function forMatch(int $organizationId, int $matchId): array
    {
        return $this->cache->remember(
            $organizationId,
            'match:phases',
            [$matchId],
            function () use ($organizationId, $matchId) {
                $phaseSql = StatisticsQuery::phaseSql('o');

                $rows = DB::table('deliveries as d')
                    ->join('overs as o', 'o.id', '=', 'd.over_id')
                    ->join('innings as i', 'i.id', '=', 'd.innings_id')
                    ->join('matches as m', 'm.id', '=', 'i.match_id')
                    ->where('m.organization_id', $organizationId)
                    ->where('m.id', $matchId)
                    ->selectRaw("
                        i.id AS innings_id,
                        i.batting_team_id,
                        {$phaseSql} AS phase,
                        SUM(d.total_runs) AS runs,
                        COUNT(*) FILTER (WHERE d.is_legal = true) AS legal_balls,
                        COUNT(*) FILTER (WHERE d.wicket = true) AS wickets,
                        COUNT(*) FILTER (WHERE d.is_legal = true AND d.total_runs = 0) AS dot_balls,
                        COUNT(*) FILTER (WHERE d.runs_off_bat IN (4,6)) AS boundaries
                    ")
                    ->groupByRaw("i.id, i.batting_team_id, {$phaseSql}")
                    ->orderBy('i.id')
                    ->get();

                return $rows->map(function ($row) {
                    $balls = (int) $row->legal_balls;
                    $runs = (int) $row->runs;

                    return [
                        'innings_id' => (int) $row->innings_id,
                        'batting_team_id' => (int) $row->batting_team_id,
                        'phase' => $row->phase,
                        'runs' => $runs,
                        'balls' => $balls,
                        'wickets' => (int) $row->wickets,
                        'run_rate' => $balls > 0 ? round(($runs * 6) / $balls, 2) : 0.0,
                        'dot_percentage' => $balls > 0
                            ? round(((int) $row->dot_balls / $balls) * 100, 2)
                            : 0.0,
                        'boundaries' => (int) $row->boundaries,
                    ];
                })->values()->all();
            }
        );
    }
}
