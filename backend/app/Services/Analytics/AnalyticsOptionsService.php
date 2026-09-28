<?php

namespace App\Services\Analytics;

use Illuminate\Support\Facades\DB;

class AnalyticsOptionsService
{
    public function options(int $organizationId): array
    {
        $seasons = DB::table('seasons')
            ->where('organization_id', $organizationId)
            ->orderByDesc('start_date')
            ->get(['id', 'name']);

        $tournaments = DB::table('tournaments')
            ->where('organization_id', $organizationId)
            ->orderByDesc('start_date')
            ->get(['id', 'name', 'season_id', 'format']);

        $teams = DB::table('teams as tm')
            ->join('clubs as c', 'c.id', '=', 'tm.club_id')
            ->where('c.organization_id', $organizationId)
            ->orderBy('tm.name')
            ->get(['tm.id', 'tm.name']);

        $venues = DB::table('venues')
            ->where('organization_id', $organizationId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $players = DB::table('players')
            ->where('organization_id', $organizationId)
            ->where('status', 'Active')
            ->orderBy('display_name')
            ->get(['id', 'display_name', 'primary_role']);

        $formats = DB::table('tournaments')
            ->where('organization_id', $organizationId)
            ->whereNotNull('format')
            ->distinct()
            ->orderBy('format')
            ->pluck('format');

        return compact(
            'seasons',
            'tournaments',
            'teams',
            'venues',
            'players',
            'formats'
        );
    }
}
