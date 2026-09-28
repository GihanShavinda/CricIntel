<?php

namespace App\Services\Analytics;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class AnalyticsQuery
{
    public function matches(int $organizationId, array $filters = []): Builder
    {
        $q = DB::table('matches as m')
            ->join('fixtures as f', 'f.id', '=', 'm.fixture_id')
            ->join('tournaments as t', 't.id', '=', 'f.tournament_id')
            ->where('m.organization_id', $organizationId);

        $this->applyMatchFilters($q, $filters);

        return $q;
    }

    public function deliveries(int $organizationId, array $filters = []): Builder
    {
        $q = DB::table('deliveries as d')
            ->join('overs as o', 'o.id', '=', 'd.over_id')
            ->join('innings as i', 'i.id', '=', 'd.innings_id')
            ->join('matches as m', 'm.id', '=', 'i.match_id')
            ->join('fixtures as f', 'f.id', '=', 'm.fixture_id')
            ->join('tournaments as t', 't.id', '=', 'f.tournament_id')
            ->where('m.organization_id', $organizationId);

        $this->applyMatchFilters($q, $filters);

        if (! empty($filters['phase'])) {
            $phase = $filters['phase'];
            $q->where(function ($phaseQuery) use ($phase) {
                if ($phase === 'powerplay') {
                    $phaseQuery->whereBetween('o.over_number', [1, 6]);
                } elseif ($phase === 'middle') {
                    $phaseQuery->whereBetween('o.over_number', [7, 15]);
                } else {
                    $phaseQuery->where('o.over_number', '>=', 16);
                }
            });
        }

        return $q;
    }

    public function applyTeamContext(Builder $q, int $teamId): Builder
    {
        return $q->where(function ($teamQuery) use ($teamId) {
            $teamQuery
                ->where('f.home_team_id', $teamId)
                ->orWhere('f.away_team_id', $teamId);
        });
    }

    public function opponentExpression(int $teamId): string
    {
        return "CASE
            WHEN f.home_team_id = {$teamId} THEN f.away_team_id
            ELSE f.home_team_id
        END";
    }

    public function phaseExpression(): string
    {
        return "CASE
            WHEN o.over_number BETWEEN 1 AND 6 THEN 'powerplay'
            WHEN o.over_number BETWEEN 7 AND 15 THEN 'middle'
            ELSE 'death'
        END";
    }

    public function bowlingTypeExpression(string $playerAlias = 'bp'): string
    {
        return "CASE
            WHEN LOWER(COALESCE({$playerAlias}.bowling_style, '')) LIKE '%spin%' THEN 'spin'
            WHEN LOWER(COALESCE({$playerAlias}.bowling_style, '')) ~ '(fast|pace|medium|seam)' THEN 'pace'
            ELSE 'other'
        END";
    }

    private function applyMatchFilters(Builder $q, array $filters): void
    {
        if (! empty($filters['season_id'])) {
            $q->where('t.season_id', $filters['season_id']);
        }

        if (! empty($filters['tournament_id'])) {
            $q->where('t.id', $filters['tournament_id']);
        }

        if (! empty($filters['venue_id'])) {
            $q->where('f.venue_id', $filters['venue_id']);
        }

        if (! empty($filters['format'])) {
            $q->where('t.format', $filters['format']);
        }

        if (! empty($filters['opponent_id'])) {
            $opponentId = (int) $filters['opponent_id'];

            $q->where(function ($opponentQuery) use ($opponentId) {
                $opponentQuery
                    ->where('f.home_team_id', $opponentId)
                    ->orWhere('f.away_team_id', $opponentId);
            });
        }

        if (! empty($filters['from'])) {
            $q->whereDate('f.scheduled_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $q->whereDate('f.scheduled_at', '<=', $filters['to']);
        }
    }
}
