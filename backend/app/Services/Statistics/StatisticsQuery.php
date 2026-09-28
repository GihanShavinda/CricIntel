<?php

namespace App\Services\Statistics;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class StatisticsQuery
{
    public function deliveries(int $organizationId, array $filters = []): Builder
    {
        $query = DB::table('deliveries as d')
            ->join('innings as i', 'i.id', '=', 'd.innings_id')
            ->join('matches as m', 'm.id', '=', 'i.match_id')
            ->join('fixtures as f', 'f.id', '=', 'm.fixture_id')
            ->where('m.organization_id', $organizationId);

        if (! empty($filters['from'])) {
            $query->whereDate('d.delivery_timestamp', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('d.delivery_timestamp', '<=', $filters['to']);
        }

        return $query;
    }

    public function wickets(int $organizationId, array $filters = []): Builder
    {
        $query = DB::table('wickets as w')
            ->join('innings as i', 'i.id', '=', 'w.innings_id')
            ->join('matches as m', 'm.id', '=', 'i.match_id')
            ->where('m.organization_id', $organizationId);

        if (! empty($filters['from'])) {
            $query->whereDate('w.created_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('w.created_at', '<=', $filters['to']);
        }

        return $query;
    }

    public static function phaseSql(string $overAlias = 'o'): string
    {
        return "CASE
            WHEN {$overAlias}.over_number BETWEEN 1 AND 6 THEN 'powerplay'
            WHEN {$overAlias}.over_number BETWEEN 7 AND 15 THEN 'middle'
            ELSE 'death'
        END";
    }

    public static function bowlingCategorySql(string $playerAlias = 'bp'): string
    {
        return "CASE
            WHEN LOWER(COALESCE({$playerAlias}.bowling_style, '')) LIKE '%spin%' THEN 'spin'
            WHEN LOWER(COALESCE({$playerAlias}.bowling_style, '')) ~ '(fast|pace|medium|seam)' THEN 'pace'
            ELSE 'other'
        END";
    }

    public static function bowlerRunsConcededSql(string $deliveryAlias = 'd'): string
    {
        return "CASE
            WHEN {$deliveryAlias}.extra_type IN ('bye','leg_bye','penalty')
                THEN {$deliveryAlias}.runs_off_bat
            ELSE {$deliveryAlias}.total_runs
        END";
    }
}
