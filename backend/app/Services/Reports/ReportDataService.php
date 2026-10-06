<?php

namespace App\Services\Reports;

use App\Models\Organization;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ReportDataService
{
    public function build(
        Organization $organization,
        string $reportType,
        array $filters
    ): ReportDocument {
        return match ($reportType) {
            'player_performance' => $this->playerPerformance($organization, $filters),
            'match' => $this->matchReport($organization, $filters),
            'team_performance' => $this->teamPerformance($organization, $filters),
            'opponent' => $this->opponentReport($organization, $filters),
            'training' => $this->trainingReport($organization, $filters),
            'scouting' => $this->scoutingReport($organization, $filters),
            'tournament' => $this->tournamentReport($organization, $filters),
            'tactical_preparation' => $this->tacticalReport($organization, $filters),
            default => throw ValidationException::withMessages([
                'report_type' => 'Unsupported CricIntel report type.',
            ]),
        };
    }

    private function playerPerformance(
        Organization $organization,
        array $filters
    ): ReportDocument {
        $playerId = $this->requiredId($filters, 'player_id', 'Player');

        $player = DB::table('players')
            ->where('id', $playerId)
            ->first();

        if (! $player) {
            throw ValidationException::withMessages(['filters.player_id' => 'Player not found.']);
        }

        $deliveries = $this->deliveryBase($organization->id, $filters)
            ->where(function ($query) use ($playerId) {
                $query->where('d.batter_id', $playerId)
                    ->orWhere('d.bowler_id', $playerId);
            })
            ->get();

        $batting = $deliveries->where('batter_id', $playerId);
        $bowling = $deliveries->where('bowler_id', $playerId);

        $runs = (int) $batting->sum('runs_off_bat');
        $balls = $batting->filter(fn ($row) => ! in_array($row->extra_type, ['wide'], true))->count();
        $dismissals = $batting->filter(fn ($row) => (bool) $row->wicket && (int) ($row->dismissed_player_id ?? 0) === $playerId)->count();
        $runsConceded = (int) $bowling->sum(fn ($row) =>
            (int) $row->runs_off_bat +
            (in_array($row->extra_type, ['wide', 'no_ball'], true) ? (int) $row->extra_runs : 0)
        );
        $legalBalls = $bowling->filter(fn ($row) => ! in_array($row->extra_type, ['wide', 'no_ball'], true))->count();
        $wickets = $bowling->filter(fn ($row) =>
            (bool) $row->wicket &&
            ! in_array($row->wicket_type, ['run_out', 'retired_hurt', 'obstructing_field'], true)
        )->count();

        $matchRows = $batting
            ->groupBy('match_id')
            ->map(function ($rows, $matchId) {
                $runs = (int) $rows->sum('runs_off_bat');
                $balls = $rows->filter(fn ($row) => $row->extra_type !== 'wide')->count();

                return [
                    'match_id' => (int) $matchId,
                    'date' => $rows->first()->scheduled_at,
                    'runs' => $runs,
                    'balls' => $balls,
                    'strike_rate' => $balls > 0 ? round($runs * 100 / $balls, 2) : 0,
                ];
            })
            ->values()
            ->all();

        $name = $this->displayName($player);

        return new ReportDocument(
            title: 'Player Performance Report',
            subtitle: $name,
            metadata: $this->metadata($organization, $filters),
            summary: [
                ['label' => 'Runs', 'value' => $runs],
                ['label' => 'Batting SR', 'value' => $balls > 0 ? round($runs * 100 / $balls, 2) : 0],
                ['label' => 'Dismissals', 'value' => $dismissals],
                ['label' => 'Wickets', 'value' => $wickets],
                ['label' => 'Economy', 'value' => $legalBalls > 0 ? round($runsConceded / ($legalBalls / 6), 2) : 0],
            ],
            sections: [
                [
                    'heading' => 'Performance overview',
                    'body' => 'This report is calculated from CricIntel delivery-level match data within the selected filters.',
                ],
            ],
            tables: [
                [
                    'title' => 'Batting by match',
                    'columns' => ['Match', 'Date', 'Runs', 'Balls', 'Strike Rate'],
                    'rows' => array_map(fn ($row) => [
                        $row['match_id'],
                        $row['date'],
                        $row['runs'],
                        $row['balls'],
                        $row['strike_rate'],
                    ], $matchRows),
                ],
            ],
            charts: [
                [
                    'title' => 'Runs by match',
                    'type' => 'bar',
                    'labels' => array_map(fn ($row) => 'M' . $row['match_id'], $matchRows),
                    'values' => array_map(fn ($row) => $row['runs'], $matchRows),
                ],
            ],
            limitations: $deliveries->isEmpty()
                ? ['No delivery data matched the selected filters.']
                : []
        );
    }

    private function matchReport(
        Organization $organization,
        array $filters
    ): ReportDocument {
        $matchId = $this->requiredId($filters, 'match_id', 'Match');

        $match = DB::table('matches as m')
            ->leftJoin('fixtures as f', 'f.id', '=', 'm.fixture_id')
            ->leftJoin('teams as ht', 'ht.id', '=', 'f.home_team_id')
            ->leftJoin('teams as at', 'at.id', '=', 'f.away_team_id')
            ->where('m.id', $matchId)
            ->select([
                'm.*',
                'f.scheduled_at',
                'f.venue_id',
                'ht.name as home_team_name',
                'at.name as away_team_name',
            ])
            ->first();

        if (! $match) {
            throw ValidationException::withMessages(['filters.match_id' => 'Match not found.']);
        }

        $innings = DB::table('innings')
            ->where('match_id', $matchId)
            ->orderBy('innings_number')
            ->get();

        $rows = $innings->map(function ($inning) {
            $deliveries = DB::table('deliveries')
                ->where('innings_id', $inning->id)
                ->get();

            $runs = (int) $deliveries->sum('total_runs');
            $wickets = $deliveries->where('wicket', true)->count();
            $legalBalls = $deliveries->filter(fn ($row) =>
                ! in_array($row->extra_type, ['wide', 'no_ball'], true)
            )->count();

            return [
                $inning->innings_number,
                $inning->batting_team_id,
                $runs,
                $wickets,
                $this->overs($legalBalls),
            ];
        })->all();

        return new ReportDocument(
            title: 'Match Report',
            subtitle: trim(($match->home_team_name ?? 'Home') . ' vs ' . ($match->away_team_name ?? 'Away')),
            metadata: [
                ...$this->metadata($organization, $filters),
                'Match ID' => $matchId,
                'Scheduled' => $match->scheduled_at ?? null,
                'Status' => $match->status ?? null,
            ],
            summary: [
                ['label' => 'Innings', 'value' => count($rows)],
                ['label' => 'Status', 'value' => $match->status ?? 'Unknown'],
            ],
            sections: [
                ['heading' => 'Match overview', 'body' => 'Score information is reconstructed from stored innings and deliveries.'],
            ],
            tables: [
                [
                    'title' => 'Innings summary',
                    'columns' => ['Innings', 'Batting Team ID', 'Runs', 'Wickets', 'Overs'],
                    'rows' => $rows,
                ],
            ],
            charts: [
                [
                    'title' => 'Innings totals',
                    'type' => 'bar',
                    'labels' => array_map(fn ($row) => 'Innings ' . $row[0], $rows),
                    'values' => array_map(fn ($row) => $row[2], $rows),
                ],
            ]
        );
    }

    private function teamPerformance(
        Organization $organization,
        array $filters
    ): ReportDocument {
        $teamId = $this->requiredId($filters, 'team_id', 'Team');
        $team = DB::table('teams')->where('id', $teamId)->first();

        if (! $team) {
            throw ValidationException::withMessages(['filters.team_id' => 'Team not found.']);
        }

        $deliveries = $this->deliveryBase($organization->id, $filters)
            ->where(function ($query) use ($teamId) {
                $query->where('i.batting_team_id', $teamId)
                    ->orWhere('i.bowling_team_id', $teamId);
            })
            ->get();

        $matchIds = $deliveries->pluck('match_id')->unique();
        $batting = $deliveries->where('batting_team_id', $teamId);
        $bowling = $deliveries->where('bowling_team_id', $teamId);

        $runsFor = (int) $batting->sum('total_runs');
        $runsAgainst = (int) $bowling->sum('total_runs');

        $byMatch = $batting->groupBy('match_id')->map(function ($rows, $id) {
            $legal = $rows->filter(fn ($r) => ! in_array($r->extra_type, ['wide', 'no_ball'], true))->count();
            $runs = (int) $rows->sum('total_runs');

            return [
                'match_id' => (int) $id,
                'date' => $rows->first()->scheduled_at,
                'runs' => $runs,
                'run_rate' => $legal > 0 ? round($runs / ($legal / 6), 2) : 0,
            ];
        })->values()->all();

        return new ReportDocument(
            title: 'Team Performance Report',
            subtitle: $team->name ?? "Team {$teamId}",
            metadata: $this->metadata($organization, $filters),
            summary: [
                ['label' => 'Matches', 'value' => $matchIds->count()],
                ['label' => 'Runs For', 'value' => $runsFor],
                ['label' => 'Runs Against', 'value' => $runsAgainst],
                ['label' => 'Run Differential', 'value' => $runsFor - $runsAgainst],
            ],
            sections: [
                ['heading' => 'Team trend', 'body' => 'The trend uses delivery-level scoring data for the selected date and competition filters.'],
            ],
            tables: [[
                'title' => 'Match scoring trend',
                'columns' => ['Match', 'Date', 'Runs', 'Run Rate'],
                'rows' => array_map(fn ($row) => [
                    $row['match_id'], $row['date'], $row['runs'], $row['run_rate']
                ], $byMatch),
            ]],
            charts: [[
                'title' => 'Runs by match',
                'type' => 'bar',
                'labels' => array_map(fn ($row) => 'M' . $row['match_id'], $byMatch),
                'values' => array_map(fn ($row) => $row['runs'], $byMatch),
            ]]
        );
    }

    private function opponentReport(
        Organization $organization,
        array $filters
    ): ReportDocument {
        $teamId = $this->requiredId($filters, 'opponent_team_id', 'Opponent team');
        $team = DB::table('teams')->where('id', $teamId)->first();

        if (! $team) {
            throw ValidationException::withMessages(['filters.opponent_team_id' => 'Opponent team not found.']);
        }

        $deliveries = $this->deliveryBase($organization->id, $filters)
            ->where(function ($query) use ($teamId) {
                $query->where('i.batting_team_id', $teamId)
                    ->orWhere('i.bowling_team_id', $teamId);
            })
            ->get();

        $batting = $deliveries->where('batting_team_id', $teamId);
        $phaseRows = collect(['powerplay', 'middle', 'death'])->map(function ($phase) use ($batting) {
            $rows = $batting->filter(fn ($row) => $this->phase((int) $row->over_number, (int) ($row->max_overs ?? 20)) === $phase);
            $legal = $rows->filter(fn ($r) => ! in_array($r->extra_type, ['wide', 'no_ball'], true))->count();
            $runs = (int) $rows->sum('total_runs');

            return [
                ucfirst($phase),
                $runs,
                $legal > 0 ? round($runs / ($legal / 6), 2) : 0,
            ];
        })->all();

        return new ReportDocument(
            title: 'Opponent Report',
            subtitle: $team->name ?? "Opponent {$teamId}",
            metadata: $this->metadata($organization, $filters),
            summary: [
                ['label' => 'Matches in sample', 'value' => $deliveries->pluck('match_id')->unique()->count()],
                ['label' => 'Runs scored', 'value' => (int) $batting->sum('total_runs')],
            ],
            sections: [
                ['heading' => 'Opponent phase profile', 'body' => 'Phase analysis is generated from recorded CricIntel deliveries and should be interpreted with sample size in mind.'],
            ],
            tables: [[
                'title' => 'Scoring by phase',
                'columns' => ['Phase', 'Runs', 'Run Rate'],
                'rows' => $phaseRows,
            ]],
            charts: [[
                'title' => 'Opponent run rate by phase',
                'type' => 'bar',
                'labels' => array_map(fn ($r) => $r[0], $phaseRows),
                'values' => array_map(fn ($r) => $r[2], $phaseRows),
            ]],
            limitations: $deliveries->count() < 30
                ? ['Small delivery sample; use this report as supporting evidence, not a standalone tactical conclusion.']
                : []
        );
    }

    private function trainingReport(
        Organization $organization,
        array $filters
    ): ReportDocument {
        $query = DB::table('training_sessions')
            ->where('organization_id', $organization->id);

        $this->applyDateFilters($query, $filters, 'session_date');

        if (! empty($filters['training_session_id'])) {
            $query->where('id', (int) $filters['training_session_id']);
        }

        if (! empty($filters['team_id'])) {
            $query->where('team_id', (int) $filters['team_id']);
        }

        $sessions = $query->orderByDesc('session_date')->get();

        $rows = $sessions->map(fn ($row) => [
            $row->id,
            $row->session_date,
            $row->session_type ?? null,
            $row->location ?? null,
            $row->duration_minutes ?? null,
            $row->status ?? null,
        ])->all();

        return new ReportDocument(
            title: 'Training Report',
            subtitle: $organization->name,
            metadata: $this->metadata($organization, $filters),
            summary: [
                ['label' => 'Sessions', 'value' => $sessions->count()],
                ['label' => 'Total minutes', 'value' => (int) $sessions->sum('duration_minutes')],
            ],
            sections: [
                ['heading' => 'Training activity', 'body' => 'Sessions are filtered by organization, team and selected date range.'],
            ],
            tables: [[
                'title' => 'Training sessions',
                'columns' => ['ID', 'Date', 'Type', 'Location', 'Minutes', 'Status'],
                'rows' => $rows,
            ]],
            charts: [[
                'title' => 'Training minutes by session',
                'type' => 'bar',
                'labels' => array_map(fn ($r) => '#' . $r[0], $rows),
                'values' => array_map(fn ($r) => (int) ($r[4] ?? 0), $rows),
            ]]
        );
    }

    private function scoutingReport(
        Organization $organization,
        array $filters
    ): ReportDocument {
        $query = DB::table('scouting_reports as sr')
            ->join('scouting_profiles as sp', 'sp.id', '=', 'sr.scouting_profile_id')
            ->leftJoin('scouting_ratings as rt', 'rt.scouting_report_id', '=', 'sr.id')
            ->where('sp.organization_id', $organization->id)
            ->select([
                'sr.*',
                'sp.display_name as player_name',
                'sp.status as profile_status',
                'rt.overall_rating',
            ]);

        if (! empty($filters['scouting_report_id'])) {
            $query->where('sr.id', (int) $filters['scouting_report_id']);
        }

        $this->applyDateFilters($query, $filters, 'sr.report_date');

        $reports = $query
            ->orderByDesc('sr.report_date')
            ->get();

        $rows = $reports->map(fn ($row) => [
            $row->id,
            $row->player_name,
            $row->overall_rating,
            $row->potential,
            $row->overall_recommendation,
            $row->profile_status,
        ])->all();

        return new ReportDocument(
            title: 'Scouting Report',
            subtitle: $organization->name,
            metadata: $this->metadata($organization, $filters),
            summary: [
                ['label' => 'Reports', 'value' => $reports->count()],
                [
                    'label' => 'Recommended',
                    'value' => $reports
                        ->whereIn(
                            'overall_recommendation',
                            ['Recommend', 'Highly Recommend']
                        )
                        ->count(),
                ],
            ],
            sections: [
                [
                    'heading' => 'Scouting assessment',
                    'body' => 'Ratings and recommendations come from stored CricIntel scouting profiles, reports and rating records; the export layer does not invent evaluations.',
                ],
            ],
            tables: [[
                'title' => 'Scouting evaluations',
                'columns' => [
                    'Report',
                    'Player',
                    'Overall',
                    'Potential',
                    'Recommendation',
                    'Profile Status',
                ],
                'rows' => $rows,
            ]],
            charts: []
        );
    }

    private function tournamentReport(
        Organization $organization,
        array $filters
    ): ReportDocument {
        $tournamentId = $this->requiredId($filters, 'tournament_id', 'Tournament');
        $tournament = DB::table('tournaments')->where('id', $tournamentId)->first();

        if (! $tournament) {
            throw ValidationException::withMessages(['filters.tournament_id' => 'Tournament not found.']);
        }

        $fixtures = DB::table('fixtures')
            ->where('tournament_id', $tournamentId)
            ->orderBy('scheduled_at')
            ->get();

        $rows = $fixtures->map(fn ($row) => [
            $row->id,
            $row->scheduled_at ?? null,
            $row->home_team_id,
            $row->away_team_id,
            $row->venue_id ?? null,
            $row->status ?? null,
        ])->all();

        return new ReportDocument(
            title: 'Tournament Report',
            subtitle: $tournament->name ?? "Tournament {$tournamentId}",
            metadata: $this->metadata($organization, $filters),
            summary: [
                ['label' => 'Fixtures', 'value' => $fixtures->count()],
                ['label' => 'Completed', 'value' => $fixtures->filter(fn ($r) => in_array(mb_strtolower((string) ($r->status ?? '')), ['completed', 'complete', 'finished'], true))->count()],
            ],
            sections: [
                ['heading' => 'Tournament schedule', 'body' => 'The export summarizes the tournament fixtures currently stored in CricIntel.'],
            ],
            tables: [[
                'title' => 'Fixtures',
                'columns' => ['Fixture', 'Scheduled', 'Home Team', 'Away Team', 'Venue', 'Status'],
                'rows' => $rows,
            ]],
            charts: []
        );
    }

    private function tacticalReport(
        Organization $organization,
        array $filters
    ): ReportDocument {
        $planId = $this->requiredId($filters, 'strategy_plan_id', 'Strategy plan');

        $plan = DB::table('strategy_plans')
            ->where('id', $planId)
            ->where('organization_id', $organization->id)
            ->first();

        if (! $plan) {
            throw ValidationException::withMessages(['filters.strategy_plan_id' => 'Strategy plan not found.']);
        }

        $sections = DB::table('strategy_sections')
            ->where('strategy_plan_id', $planId)
            ->orderBy('sort_order')
            ->get();

        $notes = DB::table('tactical_notes')
            ->where('strategy_plan_id', $planId)
            ->orderBy('created_at')
            ->get();

        return new ReportDocument(
            title: 'Tactical Preparation Report',
            subtitle: $plan->title ?? "Strategy Plan {$planId}",
            metadata: [
                ...$this->metadata($organization, $filters),
                'Plan status' => $plan->status ?? null,
            ],
            summary: [
                ['label' => 'Sections', 'value' => $sections->count()],
                ['label' => 'Tactical notes', 'value' => $notes->count()],
            ],
            sections: $sections->map(fn ($section) => [
                'heading' => $section->title ?? $section->name ?? 'Strategy section',
                'body' => $section->content ?? '',
            ])->all(),
            tables: [[
                'title' => 'Tactical notes',
                'columns' => ['ID', 'Section', 'Title', 'Status'],
                'rows' => $notes->map(fn ($note) => [
                    $note->id,
                    $note->strategy_section_id ?? null,
                    $note->title ?? null,
                    $note->status ?? null,
                ])->all(),
            ]],
            charts: []
        );
    }

    private function deliveryBase(int $organizationId, array $filters)
    {
        $query = DB::table('deliveries as d')
            ->join('innings as i', 'i.id', '=', 'd.innings_id')
            ->join('matches as m', 'm.id', '=', 'i.match_id')
            ->leftJoin('fixtures as f', 'f.id', '=', 'm.fixture_id')
            ->select([
                'd.*',
                'i.match_id',
                'i.batting_team_id',
                'i.bowling_team_id',
                'f.scheduled_at',
                'f.tournament_id',
            ]);

        if (Schema::hasColumn('matches', 'organization_id')) {
            $query->where('m.organization_id', $organizationId);
        }

        $this->applyDateFilters($query, $filters, 'f.scheduled_at');

        if (! empty($filters['match_id'])) {
            $query->where('m.id', (int) $filters['match_id']);
        }

        if (! empty($filters['tournament_id'])) {
            $query->where('f.tournament_id', (int) $filters['tournament_id']);
        }

        if (! empty($filters['venue_id'])) {
            $query->where('f.venue_id', (int) $filters['venue_id']);
        }

        if (! empty($filters['format']) && Schema::hasColumn('matches', 'format')) {
            $query->where('m.format', $filters['format']);
        }

        return $query;
    }

    private function applyDateFilters($query, array $filters, string $column): void
    {
        if (! empty($filters['date_from'])) {
            $query->whereDate($column, '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate($column, '<=', $filters['date_to']);
        }
    }

    private function metadata(Organization $organization, array $filters): array
    {
        return [
            'Organization' => $organization->name,
            'Generated' => now()->format('Y-m-d H:i'),
            'Date from' => $filters['date_from'] ?? null,
            'Date to' => $filters['date_to'] ?? null,
            'Season ID' => $filters['season_id'] ?? null,
            'Format' => $filters['format'] ?? null,
            'Phase' => $filters['phase'] ?? null,
        ];
    }

    private function requiredId(array $filters, string $key, string $label): int
    {
        if (empty($filters[$key])) {
            throw ValidationException::withMessages([
                "filters.{$key}" => "{$label} is required for this report.",
            ]);
        }

        return (int) $filters[$key];
    }

    private function displayName(object $player): string
    {
        return $player->display_name
            ?? trim(($player->first_name ?? '') . ' ' . ($player->last_name ?? ''))
            ?: 'Player #' . $player->id;
    }

    private function overs(int $legalBalls): string
    {
        return intdiv($legalBalls, 6) . '.' . ($legalBalls % 6);
    }

    private function phase(int $overNumber, int $maxOvers): string
    {
        if ($maxOvers >= 40) {
            return $overNumber <= 10 ? 'powerplay' : ($overNumber <= 40 ? 'middle' : 'death');
        }

        return $overNumber <= 6 ? 'powerplay' : ($overNumber <= 15 ? 'middle' : 'death');
    }
}
