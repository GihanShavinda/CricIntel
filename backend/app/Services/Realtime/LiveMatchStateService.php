<?php

namespace App\Services\Realtime;

use App\Models\CricketMatch;
use Illuminate\Support\Facades\DB;

class LiveMatchStateService
{
    public function snapshot(CricketMatch $match): array
    {
        $match->refresh();

        $innings = DB::table('innings')
            ->where('match_id', $match->id)
            ->orderBy('innings_number')
            ->get();

        $current = $innings
            ->where('status', 'In Progress')
            ->sortByDesc('innings_number')
            ->first()
            ?? $innings->sortByDesc('innings_number')->first();

        if (! $current) {
            return [
                'match_id' => $match->id,
                'status' => $match->status,
                'score' => null,
                'innings' => [],
                'recent_deliveries' => [],
                'last_over' => [],
                'current_batter' => null,
                'current_bowler' => null,
                'partnership' => ['runs' => 0, 'balls' => 0],
                'last_five_overs' => ['runs' => 0, 'wickets' => 0],
                'run_rate' => 0.0,
                'required_run_rate' => null,
                'target' => $match->target_runs,
            ];
        }

        $currentInningsId = (int) $current->id;

        $recentDeliveries = DB::table('deliveries as d')
            ->leftJoin('players as batter', 'batter.id', '=', 'd.batter_id')
            ->leftJoin('players as bowler', 'bowler.id', '=', 'd.bowler_id')
            ->where('d.innings_id', $currentInningsId)
            ->orderByDesc('d.sequence_number')
            ->limit(12)
            ->get([
                'd.id',
                'd.sequence_number',
                'd.ball_number',
                'd.runs_off_bat',
                'd.extra_runs',
                'd.total_runs',
                'd.extra_type',
                'd.wicket',
                'd.wicket_type',
                'd.is_legal',
                'batter.display_name as batter_name',
                'bowler.display_name as bowler_name',
            ])
            ->reverse()
            ->values()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'sequence_number' => (int) $row->sequence_number,
                'ball_number' => (int) $row->ball_number,
                'runs_off_bat' => (int) $row->runs_off_bat,
                'extra_runs' => (int) $row->extra_runs,
                'total_runs' => (int) $row->total_runs,
                'extra_type' => $row->extra_type,
                'wicket' => (bool) $row->wicket,
                'wicket_type' => $row->wicket_type,
                'is_legal' => (bool) $row->is_legal,
                'batter_name' => $row->batter_name,
                'bowler_name' => $row->bowler_name,
            ])
            ->all();

        $lastOverId = DB::table('overs')
            ->where('innings_id', $currentInningsId)
            ->orderByDesc('over_number')
            ->value('id');

        $lastOver = $lastOverId
            ? DB::table('deliveries')
                ->where('over_id', $lastOverId)
                ->orderBy('sequence_number')
                ->get()
                ->map(fn ($row) => [
                    'id' => (int) $row->id,
                    'ball_number' => (int) $row->ball_number,
                    'runs_off_bat' => (int) $row->runs_off_bat,
                    'extra_runs' => (int) $row->extra_runs,
                    'total_runs' => (int) $row->total_runs,
                    'extra_type' => $row->extra_type,
                    'wicket' => (bool) $row->wicket,
                    'is_legal' => (bool) $row->is_legal,
                ])
                ->all()
            : [];

        $currentBatter = $this->currentBatter($currentInningsId);
        $currentBowler = $this->currentBowler($currentInningsId);
        $partnership = $this->partnership($currentInningsId);
        $lastFive = $this->lastFiveOvers($currentInningsId);

        $legalBalls = (int) ($current->legal_balls ?? 0);
        $runs = (int) ($current->runs ?? 0);

        $runRate = $legalBalls > 0
            ? round(($runs * 6) / $legalBalls, 2)
            : 0.0;

        $requiredRunRate = null;

        if ($match->target_runs && $match->max_overs) {
            $remainingRuns = max(0, ((int) $match->target_runs) - $runs);
            $remainingBalls = max(0, ((int) $match->max_overs * 6) - $legalBalls);

            $requiredRunRate = $remainingBalls > 0
                ? round(($remainingRuns * 6) / $remainingBalls, 2)
                : ($remainingRuns > 0 ? null : 0.0);
        }

        return [
            'match_id' => (int) $match->id,
            'status' => $match->status,
            'result_type' => $match->result_type,
            'winner_team_id' => $match->winner_team_id,
            'target' => $match->target_runs,
            'current_innings_id' => $currentInningsId,
            'score' => [
                'runs' => $runs,
                'wickets' => (int) ($current->wickets ?? 0),
                'overs' => $this->oversNotation($legalBalls),
                'legal_balls' => $legalBalls,
                'batting_team_id' => (int) $current->batting_team_id,
                'bowling_team_id' => (int) $current->bowling_team_id,
            ],
            'run_rate' => $runRate,
            'required_run_rate' => $requiredRunRate,
            'partnership' => $partnership,
            'last_five_overs' => $lastFive,
            'current_batter' => $currentBatter,
            'current_bowler' => $currentBowler,
            'last_over' => $lastOver,
            'recent_deliveries' => $recentDeliveries,
            'innings' => $innings->map(fn ($row) => [
                'id' => (int) $row->id,
                'innings_number' => (int) $row->innings_number,
                'batting_team_id' => (int) $row->batting_team_id,
                'bowling_team_id' => (int) $row->bowling_team_id,
                'runs' => (int) $row->runs,
                'wickets' => (int) $row->wickets,
                'legal_balls' => (int) ($row->legal_balls ?? 0),
                'overs' => $this->oversNotation((int) ($row->legal_balls ?? 0)),
                'status' => $row->status,
            ])->values()->all(),
        ];
    }

    private function currentBatter(int $inningsId): ?array
    {
        $last = DB::table('deliveries')
            ->where('innings_id', $inningsId)
            ->orderByDesc('sequence_number')
            ->first();

        if (! $last) {
            return null;
        }

        $playerId = $last->wicket && $last->dismissed_player_id === $last->batter_id
            ? $last->non_striker_id
            : $last->batter_id;

        if (! $playerId) {
            return null;
        }

        $player = DB::table('players')->find($playerId);

        return $player ? [
            'id' => (int) $player->id,
            'name' => $player->display_name,
        ] : null;
    }

    private function currentBowler(int $inningsId): ?array
    {
        $bowlerId = DB::table('deliveries')
            ->where('innings_id', $inningsId)
            ->orderByDesc('sequence_number')
            ->value('bowler_id');

        if (! $bowlerId) {
            return null;
        }

        $player = DB::table('players')->find($bowlerId);

        return $player ? [
            'id' => (int) $player->id,
            'name' => $player->display_name,
        ] : null;
    }

    private function partnership(int $inningsId): array
    {
        $lastWicketSequence = DB::table('deliveries')
            ->where('innings_id', $inningsId)
            ->where('wicket', true)
            ->max('sequence_number');

        $query = DB::table('deliveries')
            ->where('innings_id', $inningsId);

        if ($lastWicketSequence) {
            $query->where('sequence_number', '>', $lastWicketSequence);
        }

        $row = $query
            ->selectRaw('
                COALESCE(SUM(total_runs), 0) AS runs,
                COUNT(*) FILTER (WHERE is_legal = true) AS balls
            ')
            ->first();

        return [
            'runs' => (int) ($row->runs ?? 0),
            'balls' => (int) ($row->balls ?? 0),
        ];
    }

    private function lastFiveOvers(int $inningsId): array
    {
        $overIds = DB::table('overs')
            ->where('innings_id', $inningsId)
            ->orderByDesc('over_number')
            ->limit(5)
            ->pluck('id');

        if ($overIds->isEmpty()) {
            return ['runs' => 0, 'wickets' => 0];
        }

        $row = DB::table('deliveries')
            ->whereIn('over_id', $overIds)
            ->selectRaw('
                COALESCE(SUM(total_runs), 0) AS runs,
                COUNT(*) FILTER (WHERE wicket = true) AS wickets
            ')
            ->first();

        return [
            'runs' => (int) ($row->runs ?? 0),
            'wickets' => (int) ($row->wickets ?? 0),
        ];
    }

    private function oversNotation(int $legalBalls): string
    {
        return intdiv($legalBalls, 6).'.'.($legalBalls % 6);
    }
}
