<?php

namespace App\Services\Analytics;

use App\Services\Statistics\StatisticsCacheService;
use Illuminate\Support\Facades\DB;

class DashboardAnalyticsService
{
    public function __construct(
        private readonly AnalyticsQuery $query,
        private readonly StatisticsCacheService $cache
    ) {}

    public function dashboard(int $organizationId, int $teamId, array $filters = []): array
    {
        return $this->cache->remember(
            $organizationId,
            'analytics:dashboard',
            [$teamId, $filters],
            fn () => [
                'team_form' => $this->teamForm($organizationId, $teamId, $filters),
                'runs_trend' => $this->runsTrend($organizationId, $teamId, $filters),
                'run_rate_trend' => $this->runRateTrend($organizationId, $teamId, $filters),
                'wicket_trend' => $this->wicketTrend($organizationId, $teamId, $filters),
                'win_loss' => $this->winLoss($organizationId, $teamId, $filters),
                'top_batters' => $this->topBatters($organizationId, $teamId, $filters),
                'top_bowlers' => $this->topBowlers($organizationId, $teamId, $filters),
                'phase_analysis' => $this->phaseAnalysis($organizationId, $teamId, $filters),
                'opponent_summary' => $this->opponentSummary($organizationId, $teamId, $filters),
                'venue_performance' => $this->venuePerformance($organizationId, $teamId, $filters),
            ]
        );
    }

    private function teamForm(int $organizationId, int $teamId, array $filters): array
    {
        return $this->query->applyTeamContext(
            $this->query->matches($organizationId, $filters),
            $teamId
        )
            ->where('m.status', 'Completed')
            ->orderByDesc('f.scheduled_at')
            ->limit(10)
            ->select([
                'm.id as match_id',
                'f.scheduled_at',
                'm.winner_team_id',
                'm.result_type',
                'f.home_team_id',
                'f.away_team_id',
            ])
            ->get()
            ->map(fn ($row) => [
                'match_id' => (int) $row->match_id,
                'date' => $row->scheduled_at,
                'result' => $row->winner_team_id === null
                    ? 'NR'
                    : ((int) $row->winner_team_id === $teamId ? 'W' : 'L'),
                'result_type' => $row->result_type,
            ])
            ->values()
            ->all();
    }

    private function runsTrend(int $organizationId, int $teamId, array $filters): array
    {
        return DB::table('innings as i')
            ->join('matches as m', 'm.id', '=', 'i.match_id')
            ->join('fixtures as f', 'f.id', '=', 'm.fixture_id')
            ->join('tournaments as t', 't.id', '=', 'f.tournament_id')
            ->where('m.organization_id', $organizationId)
            ->where('i.batting_team_id', $teamId)
            ->when(! empty($filters['season_id']), fn ($q) => $q->where('t.season_id', $filters['season_id']))
            ->when(! empty($filters['tournament_id']), fn ($q) => $q->where('t.id', $filters['tournament_id']))
            ->when(! empty($filters['venue_id']), fn ($q) => $q->where('f.venue_id', $filters['venue_id']))
            ->when(! empty($filters['format']), fn ($q) => $q->where('t.format', $filters['format']))
            ->when(! empty($filters['from']), fn ($q) => $q->whereDate('f.scheduled_at', '>=', $filters['from']))
            ->when(! empty($filters['to']), fn ($q) => $q->whereDate('f.scheduled_at', '<=', $filters['to']))
            ->orderBy('f.scheduled_at')
            ->select([
                'm.id as match_id',
                'f.scheduled_at as date',
                'i.runs',
                'i.wickets',
                'i.legal_balls',
            ])
            ->get()
            ->map(fn ($row) => [
                'match_id' => (int) $row->match_id,
                'date' => $row->date,
                'runs' => (int) $row->runs,
                'wickets' => (int) $row->wickets,
                'run_rate' => (int) $row->legal_balls > 0
                    ? round(((int) $row->runs * 6) / (int) $row->legal_balls, 2)
                    : 0.0,
            ])
            ->values()
            ->all();
    }

    private function runRateTrend(int $organizationId, int $teamId, array $filters): array
    {
        return array_map(
            fn ($row) => [
                'match_id' => $row['match_id'],
                'date' => $row['date'],
                'run_rate' => $row['run_rate'],
            ],
            $this->runsTrend($organizationId, $teamId, $filters)
        );
    }

    private function wicketTrend(int $organizationId, int $teamId, array $filters): array
    {
        return array_map(
            fn ($row) => [
                'match_id' => $row['match_id'],
                'date' => $row['date'],
                'wickets_lost' => $row['wickets'],
            ],
            $this->runsTrend($organizationId, $teamId, $filters)
        );
    }

    private function winLoss(int $organizationId, int $teamId, array $filters): array
    {
        $base = $this->query->applyTeamContext(
            $this->query->matches($organizationId, $filters),
            $teamId
        )->where('m.status', 'Completed');

        $wins = (clone $base)->where('m.winner_team_id', $teamId)->count();
        $losses = (clone $base)
            ->whereNotNull('m.winner_team_id')
            ->where('m.winner_team_id', '<>', $teamId)
            ->count();
        $noResult = (clone $base)->whereNull('m.winner_team_id')->count();

        return [
            ['name' => 'Wins', 'value' => $wins],
            ['name' => 'Losses', 'value' => $losses],
            ['name' => 'Tie/NR', 'value' => $noResult],
        ];
    }

    private function topBatters(int $organizationId, int $teamId, array $filters): array
    {
        $limit = (int) ($filters['limit'] ?? 5);

        $q = $this->query->deliveries($organizationId, $filters)
            ->join('players as p', 'p.id', '=', 'd.batter_id')
            ->where('i.batting_team_id', $teamId);

        if (! empty($filters['batting_position'])) {
            $q->join('match_players as mp', function ($join) {
                $join->on('mp.match_id', '=', 'm.id')
                    ->on('mp.player_id', '=', 'd.batter_id');
            })->where(
                'mp.batting_position',
                (int) $filters['batting_position']
            );
        }

        if (! empty($filters['bowling_type'])) {
            $q->join('players as bp', 'bp.id', '=', 'd.bowler_id');
            $typeSql = $this->query->bowlingTypeExpression('bp');
            $q->whereRaw("{$typeSql} = ?", [$filters['bowling_type']]);
        }

        return $q
            ->selectRaw('
                p.id AS player_id,
                p.display_name,
                SUM(d.runs_off_bat) AS runs,
                COUNT(*) FILTER (WHERE d.is_legal = true) AS balls,
                COUNT(*) FILTER (WHERE d.runs_off_bat = 4) AS fours,
                COUNT(*) FILTER (WHERE d.runs_off_bat = 6) AS sixes
            ')
            ->groupBy('p.id', 'p.display_name')
            ->orderByDesc('runs')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                $runs = (int) $row->runs;
                $balls = (int) $row->balls;

                return [
                    'player_id' => (int) $row->player_id,
                    'name' => $row->display_name,
                    'runs' => $runs,
                    'balls' => $balls,
                    'strike_rate' => $balls > 0 ? round(($runs / $balls) * 100, 2) : 0.0,
                    'fours' => (int) $row->fours,
                    'sixes' => (int) $row->sixes,
                ];
            })
            ->values()
            ->all();
    }

    private function topBowlers(int $organizationId, int $teamId, array $filters): array
    {
        $limit = (int) ($filters['limit'] ?? 5);

        $runsSql = "CASE
            WHEN d.extra_type IN ('bye','leg_bye','penalty') THEN d.runs_off_bat
            ELSE d.total_runs
        END";

        return $this->query->deliveries($organizationId, $filters)
            ->join('players as p', 'p.id', '=', 'd.bowler_id')
            ->where('i.bowling_team_id', $teamId)
            ->selectRaw("
                p.id AS player_id,
                p.display_name,
                COUNT(*) FILTER (WHERE d.is_legal = true) AS balls,
                SUM({$runsSql}) AS runs_conceded,
                COUNT(*) FILTER (
                    WHERE d.wicket = true
                    AND d.wicket_type NOT IN ('run_out','retired_hurt','obstructing_field')
                ) AS wickets
            ")
            ->groupBy('p.id', 'p.display_name')
            ->orderByDesc('wickets')
            ->orderBy('runs_conceded')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                $balls = (int) $row->balls;
                $runs = (int) $row->runs_conceded;

                return [
                    'player_id' => (int) $row->player_id,
                    'name' => $row->display_name,
                    'wickets' => (int) $row->wickets,
                    'runs_conceded' => $runs,
                    'balls' => $balls,
                    'economy' => $balls > 0 ? round(($runs * 6) / $balls, 2) : 0.0,
                ];
            })
            ->values()
            ->all();
    }

    private function phaseAnalysis(int $organizationId, int $teamId, array $filters): array
    {
        $phaseSql = $this->query->phaseExpression();

        return $this->query->deliveries($organizationId, $filters)
            ->where('i.batting_team_id', $teamId)
            ->selectRaw("
                {$phaseSql} AS phase,
                SUM(d.total_runs) AS runs,
                COUNT(*) FILTER (WHERE d.is_legal = true) AS balls,
                COUNT(*) FILTER (WHERE d.wicket = true) AS wickets
            ")
            ->groupByRaw($phaseSql)
            ->get()
            ->map(function ($row) {
                $runs = (int) $row->runs;
                $balls = (int) $row->balls;

                return [
                    'phase' => $row->phase,
                    'runs' => $runs,
                    'balls' => $balls,
                    'wickets' => (int) $row->wickets,
                    'run_rate' => $balls > 0 ? round(($runs * 6) / $balls, 2) : 0.0,
                ];
            })
            ->values()
            ->all();
    }

    private function opponentSummary(int $organizationId, int $teamId, array $filters): array
    {
        $opponentSql = $this->query->opponentExpression($teamId);

        $base = $this->query->applyTeamContext(
            $this->query->matches($organizationId, $filters),
            $teamId
        )->where('m.status', 'Completed');

        return $base
            ->join('teams as opp', 'opp.id', '=', DB::raw($opponentSql))
            ->selectRaw("
                opp.id AS opponent_id,
                opp.name AS opponent,
                COUNT(*) AS matches,
                COUNT(*) FILTER (WHERE m.winner_team_id = {$teamId}) AS wins,
                COUNT(*) FILTER (
                    WHERE m.winner_team_id IS NOT NULL
                    AND m.winner_team_id <> {$teamId}
                ) AS losses
            ")
            ->groupBy('opp.id', 'opp.name')
            ->orderByDesc('matches')
            ->get()
            ->map(fn ($row) => [
                'opponent_id' => (int) $row->opponent_id,
                'opponent' => $row->opponent,
                'matches' => (int) $row->matches,
                'wins' => (int) $row->wins,
                'losses' => (int) $row->losses,
            ])
            ->values()
            ->all();
    }

    private function venuePerformance(int $organizationId, int $teamId, array $filters): array
    {
        return $this->query->applyTeamContext(
            $this->query->matches($organizationId, $filters),
            $teamId
        )
            ->leftJoin('venues as v', 'v.id', '=', 'f.venue_id')
            ->where('m.status', 'Completed')
            ->selectRaw("
                v.id AS venue_id,
                COALESCE(v.name, 'Unknown venue') AS venue,
                COUNT(*) AS matches,
                COUNT(*) FILTER (WHERE m.winner_team_id = {$teamId}) AS wins,
                COUNT(*) FILTER (
                    WHERE m.winner_team_id IS NOT NULL
                    AND m.winner_team_id <> {$teamId}
                ) AS losses
            ")
            ->groupBy('v.id', 'v.name')
            ->orderByDesc('matches')
            ->get()
            ->map(function ($row) {
                $matches = (int) $row->matches;
                $wins = (int) $row->wins;

                return [
                    'venue_id' => $row->venue_id ? (int) $row->venue_id : null,
                    'venue' => $row->venue,
                    'matches' => $matches,
                    'wins' => $wins,
                    'losses' => (int) $row->losses,
                    'win_percentage' => $matches > 0 ? round(($wins / $matches) * 100, 2) : 0.0,
                ];
            })
            ->values()
            ->all();
    }
}
