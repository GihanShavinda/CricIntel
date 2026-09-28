<?php

namespace App\Services\Statistics;

use Illuminate\Support\Facades\DB;

class TeamStatisticsService
{
    public function __construct(
        private readonly StatisticsCacheService $cache
    ) {}

    public function forTeam(int $organizationId, int $teamId, array $filters = []): array
    {
        return $this->cache->remember(
            $organizationId,
            'team',
            [$teamId, $filters],
            fn () => $this->calculate($organizationId, $teamId, $filters)
        );
    }

    private function calculate(int $organizationId, int $teamId, array $filters): array
    {
        $matchesQuery = DB::table('matches as m')
            ->join('fixtures as f', 'f.id', '=', 'm.fixture_id')
            ->where('m.organization_id', $organizationId)
            ->where(function ($q) use ($teamId) {
                $q->where('f.home_team_id', $teamId)
                    ->orWhere('f.away_team_id', $teamId);
            });

        if (! empty($filters['from'])) {
            $matchesQuery->whereDate('f.scheduled_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $matchesQuery->whereDate('f.scheduled_at', '<=', $filters['to']);
        }

        $matches = (clone $matchesQuery)->distinct('m.id')->count('m.id');
        $wins = (clone $matchesQuery)->where('m.status', 'Completed')->where('m.winner_team_id', $teamId)->count();
        $losses = (clone $matchesQuery)
            ->where('m.status', 'Completed')
            ->whereNotNull('m.winner_team_id')
            ->where('m.winner_team_id', '<>', $teamId)
            ->count();
        $tiesNoResults = max(0, $matches - $wins - $losses);

        $innings = DB::table('innings as i')
            ->join('matches as m', 'm.id', '=', 'i.match_id')
            ->where('m.organization_id', $organizationId)
            ->where('i.batting_team_id', $teamId)
            ->selectRaw('
                COUNT(*) AS innings_count,
                COALESCE(SUM(i.runs),0) AS runs,
                COALESCE(SUM(i.wickets),0) AS wickets_lost,
                COALESCE(SUM(i.legal_balls),0) AS legal_balls,
                COALESCE(AVG(i.runs),0) AS average_score
            ')
            ->first();

        $phases = $this->phasePerformance($organizationId, $teamId);

        $runs = (int) ($innings->runs ?? 0);
        $balls = (int) ($innings->legal_balls ?? 0);

        return [
            'matches' => $matches,
            'wins' => $wins,
            'losses' => $losses,
            'ties_or_no_results' => $tiesNoResults,
            'win_percentage' => $matches > 0 ? round(($wins / $matches) * 100, 2) : 0.0,
            'average_score' => round((float) ($innings->average_score ?? 0), 2),
            'run_rate' => $balls > 0 ? round(($runs * 6) / $balls, 2) : 0.0,
            'wickets_lost' => (int) ($innings->wickets_lost ?? 0),
            'powerplay_performance' => $phases['powerplay'] ?? $this->emptyPhase(),
            'middle_over_performance' => $phases['middle'] ?? $this->emptyPhase(),
            'death_over_performance' => $phases['death'] ?? $this->emptyPhase(),
        ];
    }

    private function phasePerformance(int $organizationId, int $teamId): array
    {
        $phaseSql = StatisticsQuery::phaseSql('o');

        $rows = DB::table('deliveries as d')
            ->join('overs as o', 'o.id', '=', 'd.over_id')
            ->join('innings as i', 'i.id', '=', 'd.innings_id')
            ->join('matches as m', 'm.id', '=', 'i.match_id')
            ->where('m.organization_id', $organizationId)
            ->where('i.batting_team_id', $teamId)
            ->selectRaw("
                {$phaseSql} AS phase,
                COALESCE(SUM(d.total_runs),0) AS runs,
                COUNT(*) FILTER (WHERE d.is_legal = true) AS balls,
                COUNT(*) FILTER (WHERE d.wicket = true) AS wickets
            ")
            ->groupByRaw($phaseSql)
            ->get();

        $result = [];

        foreach ($rows as $row) {
            $balls = (int) $row->balls;
            $runs = (int) $row->runs;

            $result[$row->phase] = [
                'runs' => $runs,
                'balls' => $balls,
                'wickets' => (int) $row->wickets,
                'run_rate' => $balls > 0 ? round(($runs * 6) / $balls, 2) : 0.0,
            ];
        }

        return $result;
    }

    private function emptyPhase(): array
    {
        return ['runs' => 0, 'balls' => 0, 'wickets' => 0, 'run_rate' => 0.0];
    }
}
