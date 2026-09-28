<?php

namespace App\Services\Statistics;

use Illuminate\Support\Facades\DB;

class FieldingStatisticsService
{
    public function forPlayer(int $organizationId, int $playerId, array $filters = []): array
    {
        $wickets = DB::table('wickets as w')
            ->join('innings as i', 'i.id', '=', 'w.innings_id')
            ->join('matches as m', 'm.id', '=', 'i.match_id')
            ->where('m.organization_id', $organizationId)
            ->where('w.fielder_id', $playerId);

        if (! empty($filters['from'])) {
            $wickets->whereDate('w.created_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $wickets->whereDate('w.created_at', '<=', $filters['to']);
        }

        $rows = $wickets
            ->selectRaw("
                COUNT(*) FILTER (WHERE w.wicket_type = 'caught') AS catches,
                COUNT(*) FILTER (WHERE w.wicket_type = 'run_out') AS run_outs,
                COUNT(*) FILTER (WHERE w.wicket_type = 'stumped') AS stumpings
            ")
            ->first();

        $drops = 0;
        $otherOpportunities = 0;

        if (DB::getSchemaBuilder()->hasColumn('deliveries', 'fielding_event_type')) {
            $events = DB::table('deliveries as d')
                ->join('innings as i', 'i.id', '=', 'd.innings_id')
                ->join('matches as m', 'm.id', '=', 'i.match_id')
                ->where('m.organization_id', $organizationId)
                ->where('d.fielding_player_id', $playerId)
                ->selectRaw("
                    COUNT(*) FILTER (WHERE d.fielding_event_type = 'drop') AS drops,
                    COUNT(*) FILTER (WHERE d.fielding_event_type = 'opportunity') AS opportunities
                ")
                ->first();

            $drops = (int) ($events->drops ?? 0);
            $otherOpportunities = (int) ($events->opportunities ?? 0);
        }

        $catches = (int) ($rows->catches ?? 0);
        $runOuts = (int) ($rows->run_outs ?? 0);
        $stumpings = (int) ($rows->stumpings ?? 0);

        $successful = $catches + $runOuts + $stumpings;
        $opportunities = $successful + $drops + $otherOpportunities;

        return [
            'catches' => $catches,
            'run_outs' => $runOuts,
            'stumpings' => $stumpings,
            'drops' => $drops,
            'fielding_opportunities' => $opportunities,
            'fielding_efficiency' => $opportunities > 0
                ? round(($successful / $opportunities) * 100, 2)
                : null,
        ];
    }
}
