<?php

namespace App\Services\OpponentAnalytics;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class MatchupQueryService
{
    public function deliveries(
        int $organizationId,
        ?int $playerId = null,
        ?int $opponentTeamId = null,
        ?string $from = null,
        ?string $to = null,
        ?int $tournamentId = null
    ): Builder {
        $query = DB::table('deliveries as d')
            ->join('innings as i', 'i.id', '=', 'd.innings_id')
            ->join('matches as m', 'm.id', '=', 'i.match_id')
            ->leftJoin('fixtures as f', 'f.id', '=', 'm.fixture_id')
            ->leftJoin('overs as o', 'o.id', '=', 'd.over_id')
            ->leftJoin('players as batter', 'batter.id', '=', 'd.batter_id')
            ->leftJoin('players as bowler', 'bowler.id', '=', 'd.bowler_id')
            ->where('m.organization_id', $organizationId)
            ->select([
                'd.id',
                'd.innings_id',
                'd.over_id',
                'd.ball_number',
                'd.bowler_id',
                'd.batter_id',
                'd.non_striker_id',
                'd.runs_off_bat',
                'd.extra_runs',
                'd.total_runs',
                'd.extra_type',
                'd.wicket',
                'd.wicket_type',
                'd.dismissed_player_id',
                'd.shot_type',
                'd.delivery_type',
                'd.pitch_zone',
                'd.ball_speed',
                'd.created_at',
                'i.batting_team_id',
                'i.bowling_team_id',
                'm.id as match_id',
                'm.max_overs',
                'f.tournament_id',
                'f.scheduled_at',
                'o.over_number',
                'batter.batting_style as batter_batting_style',
                'bowler.bowling_style as bowler_bowling_style',
            ]);

        if ($playerId) {
            $query->where(function ($q) use ($playerId) {
                $q->where('d.batter_id', $playerId)
                    ->orWhere('d.bowler_id', $playerId);
            });
        }

        if ($opponentTeamId) {
            $query->where(function ($q) use ($opponentTeamId) {
                $q->where('i.batting_team_id', $opponentTeamId)
                    ->orWhere('i.bowling_team_id', $opponentTeamId);
            });
        }

        if ($from) {
            $query->whereDate('f.scheduled_at', '>=', $from);
        }

        if ($to) {
            $query->whereDate('f.scheduled_at', '<=', $to);
        }

        if ($tournamentId) {
            $query->where('f.tournament_id', $tournamentId);
        }

        return $query
            ->orderBy('m.id')
            ->orderBy('i.innings_number')
            ->orderBy('o.over_number')
            ->orderBy('d.ball_number')
            ->orderBy('d.id');
    }
}
