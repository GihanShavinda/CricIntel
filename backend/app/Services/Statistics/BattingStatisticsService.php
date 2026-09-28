<?php

namespace App\Services\Statistics;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BattingStatisticsService
{
    public function __construct(
        private readonly StatisticsQuery $queries
    ) {}

    public function forPlayer(int $organizationId, int $playerId, array $filters = []): array
    {
        $base = $this->queries->deliveries($organizationId, $filters)
            ->where('d.batter_id', $playerId);

        $totals = (clone $base)
            ->selectRaw('
                COUNT(DISTINCT i.match_id) AS matches,
                COUNT(DISTINCT d.innings_id) AS innings,
                COALESCE(SUM(d.runs_off_bat),0) AS runs,
                COUNT(*) FILTER (WHERE d.is_legal = true) AS balls_faced,
                COUNT(*) FILTER (WHERE d.runs_off_bat = 4) AS fours,
                COUNT(*) FILTER (WHERE d.runs_off_bat = 6) AS sixes,
                COUNT(*) FILTER (WHERE d.is_legal = true AND d.total_runs = 0) AS dot_balls
            ')
            ->first();

        $dismissals = DB::table('wickets as w')
            ->join('innings as i', 'i.id', '=', 'w.innings_id')
            ->join('matches as m', 'm.id', '=', 'i.match_id')
            ->where('m.organization_id', $organizationId)
            ->where('w.dismissed_player_id', $playerId)
            ->whereNotIn('w.wicket_type', ['retired_hurt'])
            ->count();

        $inningsScores = (clone $base)
            ->selectRaw('d.innings_id, SUM(d.runs_off_bat) AS runs')
            ->groupBy('d.innings_id')
            ->pluck('runs')
            ->map(fn ($value) => (int) $value);

        $runs = (int) ($totals->runs ?? 0);
        $balls = (int) ($totals->balls_faced ?? 0);
        $fours = (int) ($totals->fours ?? 0);
        $sixes = (int) ($totals->sixes ?? 0);
        $dots = (int) ($totals->dot_balls ?? 0);
        $boundaryRuns = ($fours * 4) + ($sixes * 6);

        $phaseRows = $this->phaseSplit($organizationId, $playerId, $filters);
        $bowlingTypeRows = $this->bowlingTypeSplit($organizationId, $playerId, $filters);

        return [
            'matches' => (int) ($totals->matches ?? 0),
            'innings' => (int) ($totals->innings ?? 0),
            'runs' => $runs,
            'balls_faced' => $balls,
            'highest_score' => $inningsScores->max() ?? 0,
            'average' => $dismissals > 0 ? round($runs / $dismissals, 2) : null,
            'strike_rate' => $balls > 0 ? round(($runs / $balls) * 100, 2) : 0.0,
            'fifties' => $inningsScores->filter(fn ($r) => $r >= 50 && $r < 100)->count(),
            'hundreds' => $inningsScores->filter(fn ($r) => $r >= 100)->count(),
            'fours' => $fours,
            'sixes' => $sixes,
            'boundary_percentage' => $runs > 0 ? round(($boundaryRuns / $runs) * 100, 2) : 0.0,
            'dot_ball_percentage' => $balls > 0 ? round(($dots / $balls) * 100, 2) : 0.0,
            'powerplay_strike_rate' => $phaseRows['powerplay']['strike_rate'] ?? 0.0,
            'middle_over_strike_rate' => $phaseRows['middle']['strike_rate'] ?? 0.0,
            'death_over_strike_rate' => $phaseRows['death']['strike_rate'] ?? 0.0,
            'spin_strike_rate' => $bowlingTypeRows['spin']['strike_rate'] ?? 0.0,
            'pace_strike_rate' => $bowlingTypeRows['pace']['strike_rate'] ?? 0.0,
            'dismissals' => $dismissals,
            'phases' => $phaseRows,
            'bowling_types' => $bowlingTypeRows,
        ];
    }

    private function phaseSplit(int $organizationId, int $playerId, array $filters): array
    {
        $phaseSql = StatisticsQuery::phaseSql('o');

        $rows = $this->queries->deliveries($organizationId, $filters)
            ->join('overs as o', 'o.id', '=', 'd.over_id')
            ->where('d.batter_id', $playerId)
            ->selectRaw("
                {$phaseSql} AS phase,
                COALESCE(SUM(d.runs_off_bat),0) AS runs,
                COUNT(*) FILTER (WHERE d.is_legal = true) AS balls
            ")
            ->groupByRaw($phaseSql)
            ->get();

        return $this->rateRows($rows);
    }

    private function bowlingTypeSplit(int $organizationId, int $playerId, array $filters): array
    {
        $categorySql = StatisticsQuery::bowlingCategorySql('bp');

        $rows = $this->queries->deliveries($organizationId, $filters)
            ->join('players as bp', 'bp.id', '=', 'd.bowler_id')
            ->where('d.batter_id', $playerId)
            ->selectRaw("
                {$categorySql} AS category,
                COALESCE(SUM(d.runs_off_bat),0) AS runs,
                COUNT(*) FILTER (WHERE d.is_legal = true) AS balls
            ")
            ->groupByRaw($categorySql)
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $balls = (int) $row->balls;
            $runs = (int) $row->runs;
            $result[$row->category] = [
                'runs' => $runs,
                'balls' => $balls,
                'strike_rate' => $balls > 0 ? round(($runs / $balls) * 100, 2) : 0.0,
            ];
        }

        return $result;
    }

    private function rateRows(Collection $rows): array
    {
        $result = [];

        foreach ($rows as $row) {
            $balls = (int) $row->balls;
            $runs = (int) $row->runs;

            $result[$row->phase] = [
                'runs' => $runs,
                'balls' => $balls,
                'strike_rate' => $balls > 0 ? round(($runs / $balls) * 100, 2) : 0.0,
            ];
        }

        return $result;
    }
}
