<?php

namespace App\Services\Statistics;

use Illuminate\Support\Facades\DB;

class MatchStatisticsService
{
    public function __construct(
        private readonly StatisticsCacheService $cache,
        private readonly PhaseStatisticsService $phases
    ) {}

    public function forMatch(int $organizationId, int $matchId): array
    {
        return $this->cache->remember(
            $organizationId,
            'match',
            [$matchId],
            function () use ($organizationId, $matchId) {
                $match = DB::table('matches as m')
                    ->join('fixtures as f', 'f.id', '=', 'm.fixture_id')
                    ->where('m.organization_id', $organizationId)
                    ->where('m.id', $matchId)
                    ->select([
                        'm.id',
                        'm.status',
                        'm.result_type',
                        'm.winner_team_id',
                        'm.player_of_match_id',
                        'm.target_runs',
                        'm.max_overs',
                        'f.home_team_id',
                        'f.away_team_id',
                        'f.scheduled_at',
                    ])
                    ->first();

                abort_unless($match, 404, 'Match not found.');

                $innings = DB::table('innings as i')
                    ->where('i.match_id', $matchId)
                    ->orderBy('i.innings_number')
                    ->get()
                    ->map(fn ($row) => [
                        'id' => (int) $row->id,
                        'innings_number' => (int) $row->innings_number,
                        'batting_team_id' => (int) $row->batting_team_id,
                        'bowling_team_id' => (int) $row->bowling_team_id,
                        'runs' => (int) $row->runs,
                        'wickets' => (int) $row->wickets,
                        'balls' => (int) $row->legal_balls,
                        'overs' => intdiv((int) $row->legal_balls, 6).'.'.((int) $row->legal_balls % 6),
                        'run_rate' => (int) $row->legal_balls > 0
                            ? round(((int) $row->runs * 6) / (int) $row->legal_balls, 2)
                            : 0.0,
                        'status' => $row->status,
                    ])
                    ->values()
                    ->all();

                $boundaries = DB::table('deliveries as d')
                    ->join('innings as i', 'i.id', '=', 'd.innings_id')
                    ->where('i.match_id', $matchId)
                    ->selectRaw("
                        COUNT(*) FILTER (WHERE d.runs_off_bat = 4) AS fours,
                        COUNT(*) FILTER (WHERE d.runs_off_bat = 6) AS sixes,
                        COUNT(*) FILTER (WHERE d.is_legal = true AND d.total_runs = 0) AS dots,
                        COUNT(*) FILTER (WHERE d.extra_type = 'wide') AS wides,
                        COUNT(*) FILTER (WHERE d.extra_type = 'no_ball') AS no_balls
                    ")
                    ->first();

                return [
                    'match' => $match,
                    'innings' => $innings,
                    'summary' => [
                        'fours' => (int) ($boundaries->fours ?? 0),
                        'sixes' => (int) ($boundaries->sixes ?? 0),
                        'dot_balls' => (int) ($boundaries->dots ?? 0),
                        'wides' => (int) ($boundaries->wides ?? 0),
                        'no_balls' => (int) ($boundaries->no_balls ?? 0),
                    ],
                    'phases' => $this->phases->forMatch($organizationId, $matchId),
                ];
            }
        );
    }
}
