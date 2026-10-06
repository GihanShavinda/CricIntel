<?php

namespace App\Services\StrategyAi;

use App\Models\Organization;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class StrategyContextBuilder
{
    public function build(
        Organization $organization,
        int $matchId,
        bool $includeScouting
    ): array {
        $match = $this->matchContext($organization->id, $matchId);

        if (! $match) {
            throw ValidationException::withMessages([
                'match_id' => 'The match does not exist in this organization.',
            ]);
        }

        $evidence = new EvidenceFactory();
        $limitations = [];

        [$focusTeamId, $opponentTeamId, $focusReason] =
            $this->inferTeams($organization->id, $match);

        if (! $focusTeamId || ! $opponentTeamId) {
            $limitations[] =
                'CricIntel could not confidently infer both the focus team and opponent from this match.';
        }

        if ($focusReason) {
            $limitations[] = $focusReason;
        }

        $cutoff = $match->scheduled_at ?: now()->toDateTimeString();
        $maxOvers = (int) ($match->max_overs ?: 20);

        $selectedSquad = $this->selectedSquad(
            $organization->id,
            $matchId,
            $focusTeamId,
            $evidence
        );

        $focusPlayerIds = collect($selectedSquad['playing_xi'])
            ->pluck('player_id')
            ->filter()
            ->values();

        if ($focusPlayerIds->isEmpty()) {
            $focusPlayerIds = collect($selectedSquad['match_squad'])
                ->pluck('player_id')
                ->filter()
                ->values();
        }

        if ($focusPlayerIds->isEmpty() && $focusTeamId) {
            $focusPlayerIds = $this->teamPlayerIds($focusTeamId);
            $limitations[] =
                'No confirmed Playing XI or populated match squad was found, so focus-team roster players were used.';
        }

        $opponentPlayerIds = $opponentTeamId
            ? $this->teamPlayerIds($opponentTeamId)
            : collect();

        $availablePlayers = $this->availablePlayers(
            $focusPlayerIds,
            $cutoff,
            $evidence
        );

        $recentForm = $this->recentBattingForm(
            $organization->id,
            $focusPlayerIds,
            $cutoff,
            $evidence
        );

        $playerStats = $this->playerCareerStats(
            $organization->id,
            $focusPlayerIds,
            $cutoff,
            $evidence,
            'focus'
        );

        $focusBowlingPhases = $this->bowlingPhaseStats(
            $organization->id,
            $focusPlayerIds,
            $cutoff,
            $maxOvers,
            $evidence
        );

        $opponentBatterPhases = $this->battingPhaseStats(
            $organization->id,
            $opponentPlayerIds,
            $cutoff,
            $maxOvers,
            $evidence
        );

        $opponentStats = $this->playerCareerStats(
            $organization->id,
            $opponentPlayerIds,
            $cutoff,
            $evidence,
            'opponent'
        );

        $venueStats = $this->venueStats(
            $organization->id,
            $match->venue_id ? (int) $match->venue_id : null,
            $cutoff,
            $evidence
        );

        $matchups = $this->matchups(
            $organization->id,
            $focusPlayerIds,
            $opponentPlayerIds,
            $cutoff,
            $evidence
        );

        $training = $this->trainingContext(
            $organization->id,
            $focusPlayerIds
        );

        $scouting = $includeScouting
            ? $this->scoutingContext(
                $organization->id,
                $opponentPlayerIds->merge($focusPlayerIds)->unique()->values()
            )
            : [
                'authorized' => false,
                'reports' => [],
                'notes' => [],
                'limitation' =>
                    'Scouting notes were not included because this request is not authorized for scouting context.',
            ];

        $context = [
            'schema_version' => 'p15.strategy-context.v1',
            'generated_at' => now()->toIso8601String(),

            'match' => [
                'id' => (int) $match->match_id,
                'scheduled_at' => $match->scheduled_at,
                'status' => $match->match_status,
                'max_overs' => $maxOvers,
                'home_team' => [
                    'id' => (int) $match->home_team_id,
                    'name' => $match->home_team_name,
                ],
                'away_team' => [
                    'id' => (int) $match->away_team_id,
                    'name' => $match->away_team_name,
                ],
                'focus_team_id' => $focusTeamId,
                'opponent_team_id' => $opponentTeamId,
                'venue' => [
                    'id' => $match->venue_id ? (int) $match->venue_id : null,
                    'name' => $match->venue_name,
                    'city' => $match->venue_city,
                    'country' => $match->venue_country,
                    'pitch_type' => $match->pitch_type,
                    'notes' => $match->venue_notes,
                ],
                'source' => [
                    'tables' => ['matches', 'fixtures', 'teams', 'venues'],
                    'organization_id' => $organization->id,
                    'match_id' => $matchId,
                ],
            ],

            'available_players' => $availablePlayers,

            'recent_form' => $recentForm,

            'player_data' => [
                'career_stats' => $playerStats,
                'bowling_phase_stats' => $focusBowlingPhases,
            ],

            'opponent_data' => [
                'career_stats' => $opponentStats,
                'batter_phase_stats' => $opponentBatterPhases,
            ],

            'venue_statistics' => $venueStats,

            'matchups' => $matchups,

            'training_context' => $training,

            'scouting_context' => $scouting,

            'selected_squad' => $selectedSquad,

            'limitations' => $limitations,

            'evidence' => $evidence->items(),
        ];

        return $context;
    }

    private function matchContext(int $organizationId, int $matchId): ?object
    {
        return DB::table('matches as m')
            ->join('fixtures as f', 'f.id', '=', 'm.fixture_id')
            ->join('teams as ht', 'ht.id', '=', 'f.home_team_id')
            ->join('teams as at', 'at.id', '=', 'f.away_team_id')
            ->leftJoin('venues as v', 'v.id', '=', 'f.venue_id')
            ->where('m.organization_id', $organizationId)
            ->where('m.id', $matchId)
            ->first([
                'm.id as match_id',
                'm.status as match_status',
                'm.max_overs',
                'f.scheduled_at',
                'f.home_team_id',
                'ht.name as home_team_name',
                'f.away_team_id',
                'at.name as away_team_name',
                'f.venue_id as venue_id',
                'v.name as venue_name',
                'v.city as venue_city',
                'v.country as venue_country',
                'v.pitch_type',
                'v.notes as venue_notes',
            ]);
    }

    private function inferTeams(int $organizationId, object $match): array
    {
        if (Schema::hasTable('match_squads')) {
            $matchSquad = DB::table('match_squads')
                ->where('organization_id', $organizationId)
                ->where('match_id', $match->match_id)
                ->orderByRaw(
                    "CASE WHEN LOWER(status) = 'confirmed' THEN 0 " .
                    "WHEN LOWER(status) = 'active' THEN 1 ELSE 2 END"
                )
                ->orderBy('id')
                ->first();

            if ($matchSquad) {
                $focus = (int) $matchSquad->team_id;
                $opponent = $focus === (int) $match->home_team_id
                    ? (int) $match->away_team_id
                    : (int) $match->home_team_id;

                return [$focus, $opponent, null];
            }
        }

        $orgTeamIds = DB::table('teams as t')
            ->join('clubs as c', 'c.id', '=', 't.club_id')
            ->where('c.organization_id', $organizationId)
            ->whereIn('t.id', [
                (int) $match->home_team_id,
                (int) $match->away_team_id,
            ])
            ->pluck('t.id')
            ->map(fn ($id) => (int) $id);

        if ($orgTeamIds->count() === 1) {
            $focus = $orgTeamIds->first();
            $opponent = $focus === (int) $match->home_team_id
                ? (int) $match->away_team_id
                : (int) $match->home_team_id;

            return [$focus, $opponent, null];
        }

        return [
            (int) $match->home_team_id,
            (int) $match->away_team_id,
            'No match squad uniquely identified the focus team; CricIntel used the fixture home team as the focus team.',
        ];
    }

    private function teamPlayerIds(int $teamId): Collection
    {
        if (! Schema::hasTable('player_team')) {
            return collect();
        }

        return DB::table('player_team')
            ->where('team_id', $teamId)
            ->pluck('player_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

    private function selectedSquad(
        int $organizationId,
        int $matchId,
        ?int $focusTeamId,
        EvidenceFactory $evidence
    ): array {
        if (
            ! $focusTeamId ||
            ! Schema::hasTable('match_squads')
        ) {
            return [
                'match_squad_id' => null,
                'status' => null,
                'match_squad' => [],
                'playing_xi' => [],
                'batting_order' => [],
                'bowling_assignments' => [],
            ];
        }

        $squad = DB::table('match_squads')
            ->where('organization_id', $organizationId)
            ->where('match_id', $matchId)
            ->where('team_id', $focusTeamId)
            ->first();

        if (! $squad) {
            return [
                'match_squad_id' => null,
                'status' => null,
                'match_squad' => [],
                'playing_xi' => [],
                'batting_order' => [],
                'bowling_assignments' => [],
            ];
        }

        $matchSquad = Schema::hasTable('match_squad_players')
            ? DB::table('match_squad_players as msp')
                ->join('players as p', 'p.id', '=', 'msp.player_id')
                ->where('msp.match_squad_id', $squad->id)
                ->orderBy('p.display_name')
                ->get([
                    'msp.player_id',
                    'msp.selection_status',
                    'msp.override_used',
                    'msp.override_reason',
                    'p.display_name',
                    'p.primary_role',
                    'p.batting_style',
                    'p.bowling_style',
                    'p.status as player_status',
                ])
            : collect();

        $xi = Schema::hasTable('playing_xi')
            ? DB::table('playing_xi as px')
                ->join('players as p', 'p.id', '=', 'px.player_id')
                ->where('px.match_squad_id', $squad->id)
                ->orderBy('p.display_name')
                ->get([
                    'px.player_id',
                    'px.is_captain',
                    'px.is_wicketkeeper',
                    'p.display_name',
                    'p.primary_role',
                    'p.batting_style',
                    'p.bowling_style',
                    'p.status as player_status',
                ])
            : collect();

        $xi = $xi->map(function ($row) use ($evidence, $matchId, $focusTeamId) {
            $evidenceId = $evidence->add(
                'playing_xi_selection',
                1,
                'selected',
                1,
                [
                    'type' => 'player',
                    'id' => (int) $row->player_id,
                    'name' => $row->display_name,
                ],
                [
                    'tables' => ['match_squads', 'playing_xi', 'players'],
                    'match_id' => $matchId,
                    'team_id' => $focusTeamId,
                    'scope' => 'current selected XI',
                ]
            );

            return [
                ...((array) $row),
                'selection_evidence_id' => $evidenceId,
            ];
        })->values();

        $battingOrder = Schema::hasTable('batting_orders')
            ? DB::table('batting_orders as bo')
                ->join('players as p', 'p.id', '=', 'bo.player_id')
                ->where('bo.match_squad_id', $squad->id)
                ->orderBy('bo.position')
                ->get([
                    'bo.player_id',
                    'bo.position',
                    'p.display_name',
                ])
                ->map(fn ($row) => (array) $row)
                ->values()
                ->all()
            : [];

        $bowling = Schema::hasTable('bowling_assignments')
            ? DB::table('bowling_assignments as ba')
                ->join('players as p', 'p.id', '=', 'ba.player_id')
                ->where('ba.match_squad_id', $squad->id)
                ->orderBy('ba.phase')
                ->orderBy('ba.priority')
                ->get([
                    'ba.player_id',
                    'ba.phase',
                    'ba.priority',
                    'ba.notes',
                    'p.display_name',
                ])
                ->map(fn ($row) => (array) $row)
                ->values()
                ->all()
            : [];

        return [
            'match_squad_id' => (int) $squad->id,
            'status' => $squad->status,
            'match_squad' => $matchSquad
                ->map(fn ($row) => (array) $row)
                ->values()
                ->all(),
            'playing_xi' => $xi->all(),
            'batting_order' => $battingOrder,
            'bowling_assignments' => $bowling,
        ];
    }

    private function availablePlayers(
        Collection $playerIds,
        string $cutoff,
        EvidenceFactory $evidence
    ): array {
        if ($playerIds->isEmpty()) {
            return [];
        }

        $players = DB::table('players')
            ->whereIn('id', $playerIds)
            ->orderBy('display_name')
            ->get([
                'id',
                'display_name',
                'primary_role',
                'batting_style',
                'bowling_style',
                'status',
            ]);

        $availabilityTable = null;
        foreach (['player_availabilities', 'player_availability'] as $candidate) {
            if (Schema::hasTable($candidate)) {
                $availabilityTable = $candidate;
                break;
            }
        }

        return $players->map(function ($player) use (
            $availabilityTable,
            $cutoff,
            $evidence
        ) {
            $availability = null;

            if ($availabilityTable) {
                $query = DB::table($availabilityTable)
                    ->where('player_id', $player->id);

                if (
                    Schema::hasColumn($availabilityTable, 'available_from') &&
                    Schema::hasColumn($availabilityTable, 'available_to')
                ) {
                    $date = substr($cutoff, 0, 10);
                    $query->where(function ($builder) use ($date) {
                        $builder->whereNull('available_from')
                            ->orWhereDate('available_from', '<=', $date);
                    })->where(function ($builder) use ($date) {
                        $builder->whereNull('available_to')
                            ->orWhereDate('available_to', '>=', $date);
                    });
                }

                $availability = $query->orderByDesc('id')->first();
            }

            $status = $availability->status ?? $player->status ?? 'Unknown';
            $isAvailable = in_array(
                mb_strtolower((string) $status),
                ['active', 'available', 'conditional'],
                true
            );

            $evidenceId = $evidence->add(
                'player_availability_status',
                $status,
                null,
                1,
                [
                    'type' => 'player',
                    'id' => (int) $player->id,
                    'name' => $player->display_name,
                ],
                [
                    'tables' => array_values(array_filter([
                        'players',
                        $availabilityTable,
                    ])),
                    'cutoff' => $cutoff,
                    'scope' => 'current match availability',
                ]
            );

            return [
                'player_id' => (int) $player->id,
                'player_name' => $player->display_name,
                'primary_role' => $player->primary_role,
                'batting_style' => $player->batting_style,
                'bowling_style' => $player->bowling_style,
                'status' => $status,
                'is_available' => $isAvailable,
                'reason' => $availability->reason ?? null,
                'evidence_id' => $evidenceId,
            ];
        })->values()->all();
    }

    private function recentBattingForm(
        int $organizationId,
        Collection $playerIds,
        string $cutoff,
        EvidenceFactory $evidence
    ): array {
        if ($playerIds->isEmpty()) {
            return [];
        }

        $rows = DB::table('deliveries as d')
            ->join('innings as i', 'i.id', '=', 'd.innings_id')
            ->join('matches as m', 'm.id', '=', 'i.match_id')
            ->join('fixtures as f', 'f.id', '=', 'm.fixture_id')
            ->join('players as p', 'p.id', '=', 'd.batter_id')
            ->where('m.organization_id', $organizationId)
            ->whereIn('d.batter_id', $playerIds)
            ->where('f.scheduled_at', '<', $cutoff)
            ->whereRaw("LOWER(COALESCE(i.status, '')) = 'completed'")
            ->groupBy([
                'd.batter_id',
                'p.display_name',
                'm.id',
                'i.id',
                'i.innings_number',
                'f.scheduled_at',
            ])
            ->orderBy('d.batter_id')
            ->orderByDesc('f.scheduled_at')
            ->selectRaw(
                "d.batter_id as player_id, p.display_name as player_name, " .
                "m.id as match_id, i.id as innings_id, i.innings_number, f.scheduled_at, " .
                "SUM(d.runs_off_bat)::int as runs, " .
                "COUNT(*) FILTER (WHERE d.is_legal = true)::int as balls"
            )
            ->get()
            ->groupBy('player_id');

        $result = [];

        foreach ($rows as $playerId => $playerRows) {
            $recent = collect($playerRows)->take(5)->values();
            $runs = (int) $recent->sum('runs');
            $balls = (int) $recent->sum('balls');
            $innings = $recent->count();
            $averageRuns = $innings > 0 ? round($runs / $innings, 2) : null;
            $strikeRate = $balls > 0 ? round($runs / $balls * 100, 2) : null;
            $name = $recent->first()->player_name;

            $avgId = $evidence->add(
                'recent_5_innings_average_runs',
                $averageRuns,
                'runs',
                $innings,
                [
                    'type' => 'player',
                    'id' => (int) $playerId,
                    'name' => $name,
                ],
                [
                    'tables' => ['deliveries', 'innings', 'matches', 'fixtures'],
                    'cutoff' => $cutoff,
                    'scope' => 'last up to 5 completed batting innings before match',
                ]
            );

            $srId = $evidence->add(
                'recent_5_innings_strike_rate',
                $strikeRate,
                'runs_per_100_balls',
                $balls,
                [
                    'type' => 'player',
                    'id' => (int) $playerId,
                    'name' => $name,
                ],
                [
                    'tables' => ['deliveries', 'innings', 'matches', 'fixtures'],
                    'cutoff' => $cutoff,
                    'scope' => 'last up to 5 completed batting innings before match',
                ]
            );

            $result[] = [
                'player_id' => (int) $playerId,
                'player_name' => $name,
                'innings' => $innings,
                'runs' => $runs,
                'balls' => $balls,
                'average_runs' => $averageRuns,
                'strike_rate' => $strikeRate,
                'average_runs_evidence_id' => $avgId,
                'strike_rate_evidence_id' => $srId,
                'provenance' => [
                    'tables' => ['deliveries', 'innings', 'matches', 'fixtures'],
                    'cutoff' => $cutoff,
                    'scope' => 'last up to 5 completed batting innings before match',
                ],
            ];
        }

        return $result;
    }

    private function playerCareerStats(
        int $organizationId,
        Collection $playerIds,
        string $cutoff,
        EvidenceFactory $evidence,
        string $scope
    ): array {
        if ($playerIds->isEmpty()) {
            return [];
        }

        $batting = DB::table('deliveries as d')
            ->join('innings as i', 'i.id', '=', 'd.innings_id')
            ->join('matches as m', 'm.id', '=', 'i.match_id')
            ->join('fixtures as f', 'f.id', '=', 'm.fixture_id')
            ->join('players as p', 'p.id', '=', 'd.batter_id')
            ->where('m.organization_id', $organizationId)
            ->whereIn('d.batter_id', $playerIds)
            ->where('f.scheduled_at', '<', $cutoff)
            ->whereRaw("LOWER(COALESCE(i.status, '')) = 'completed'")
            ->groupBy(['d.batter_id', 'p.display_name'])
            ->selectRaw(
                "d.batter_id as player_id, p.display_name as player_name, " .
                "SUM(d.runs_off_bat)::int as runs, " .
                "COUNT(*) FILTER (WHERE d.is_legal = true)::int as balls, " .
                "COUNT(DISTINCT i.id)::int as innings, " .
                "SUM(CASE WHEN d.dismissed_player_id = d.batter_id THEN 1 ELSE 0 END)::int as dismissals"
            )
            ->get()
            ->keyBy('player_id');

        $bowling = DB::table('deliveries as d')
            ->join('innings as i', 'i.id', '=', 'd.innings_id')
            ->join('matches as m', 'm.id', '=', 'i.match_id')
            ->join('fixtures as f', 'f.id', '=', 'm.fixture_id')
            ->join('players as p', 'p.id', '=', 'd.bowler_id')
            ->where('m.organization_id', $organizationId)
            ->whereIn('d.bowler_id', $playerIds)
            ->where('f.scheduled_at', '<', $cutoff)
            ->whereRaw("LOWER(COALESCE(i.status, '')) = 'completed'")
            ->groupBy(['d.bowler_id', 'p.display_name'])
            ->selectRaw(
                "d.bowler_id as player_id, p.display_name as player_name, " .
                "COUNT(*) FILTER (WHERE d.is_legal = true)::int as balls, " .
                "SUM(CASE WHEN COALESCE(d.extra_type, 'none') IN ('bye','leg_bye','leg bye') " .
                "THEN d.runs_off_bat ELSE d.runs_off_bat + d.extra_runs END)::int as runs_conceded, " .
                "SUM(CASE WHEN d.wicket = true AND COALESCE(LOWER(d.wicket_type), '') NOT IN " .
                "('run_out','run out','retired_hurt','retired hurt','obstructing_field','obstructing field') " .
                "THEN 1 ELSE 0 END)::int as wickets"
            )
            ->get()
            ->keyBy('player_id');

        $result = [];

        foreach ($playerIds as $playerId) {
            $bat = $batting->get($playerId);
            $bowl = $bowling->get($playerId);

            if (! $bat && ! $bowl) {
                continue;
            }

            $name = $bat->player_name ?? $bowl->player_name ?? "Player {$playerId}";
            $batRuns = (int) ($bat->runs ?? 0);
            $batBalls = (int) ($bat->balls ?? 0);
            $batInnings = (int) ($bat->innings ?? 0);
            $dismissals = (int) ($bat->dismissals ?? 0);
            $batSr = $batBalls > 0 ? round($batRuns / $batBalls * 100, 2) : null;
            $batAvg = $dismissals > 0 ? round($batRuns / $dismissals, 2) : null;

            $bowlBalls = (int) ($bowl->balls ?? 0);
            $runsConceded = (int) ($bowl->runs_conceded ?? 0);
            $wickets = (int) ($bowl->wickets ?? 0);
            $economy = $bowlBalls > 0
                ? round($runsConceded / $bowlBalls * 6, 2)
                : null;

            $entity = [
                'type' => 'player',
                'id' => (int) $playerId,
                'name' => $name,
            ];

            $source = [
                'tables' => ['deliveries', 'innings', 'matches', 'fixtures'],
                'cutoff' => $cutoff,
                'scope' => "{$scope} player completed history before match",
            ];

            $result[] = [
                'player_id' => (int) $playerId,
                'player_name' => $name,
                'batting' => [
                    'innings' => $batInnings,
                    'runs' => $batRuns,
                    'balls' => $batBalls,
                    'dismissals' => $dismissals,
                    'average' => $batAvg,
                    'strike_rate' => $batSr,
                    'runs_evidence_id' => $evidence->add(
                        'career_batting_runs', $batRuns, 'runs', $batInnings, $entity, $source
                    ),
                    'average_evidence_id' => $evidence->add(
                        'career_batting_average', $batAvg, 'runs_per_dismissal', $dismissals, $entity, $source
                    ),
                    'strike_rate_evidence_id' => $evidence->add(
                        'career_batting_strike_rate', $batSr, 'runs_per_100_balls', $batBalls, $entity, $source
                    ),
                ],
                'bowling' => [
                    'balls' => $bowlBalls,
                    'runs_conceded' => $runsConceded,
                    'wickets' => $wickets,
                    'economy' => $economy,
                    'wickets_evidence_id' => $evidence->add(
                        'career_bowling_wickets', $wickets, 'wickets', $bowlBalls, $entity, $source
                    ),
                    'economy_evidence_id' => $evidence->add(
                        'career_bowling_economy', $economy, 'runs_per_6_balls', $bowlBalls, $entity, $source
                    ),
                ],
                'provenance' => $source,
            ];
        }

        return $result;
    }

    private function bowlingPhaseStats(
        int $organizationId,
        Collection $playerIds,
        string $cutoff,
        int $maxOvers,
        EvidenceFactory $evidence
    ): array {
        if ($playerIds->isEmpty() || ! Schema::hasTable('overs')) {
            return [];
        }

        [$powerplayEnd, $deathStart] = $this->phaseBounds($maxOvers);

        $rows = DB::table('deliveries as d')
            ->join('overs as o', 'o.id', '=', 'd.over_id')
            ->join('innings as i', 'i.id', '=', 'd.innings_id')
            ->join('matches as m', 'm.id', '=', 'i.match_id')
            ->join('fixtures as f', 'f.id', '=', 'm.fixture_id')
            ->join('players as bowler', 'bowler.id', '=', 'd.bowler_id')
            ->leftJoin('players as batter', 'batter.id', '=', 'd.batter_id')
            ->where('m.organization_id', $organizationId)
            ->whereIn('d.bowler_id', $playerIds)
            ->where('f.scheduled_at', '<', $cutoff)
            ->whereRaw("LOWER(COALESCE(i.status, '')) = 'completed'")
            ->groupBy([
                'd.bowler_id',
                'bowler.display_name',
                DB::raw(
                    "CASE WHEN o.over_number <= {$powerplayEnd} THEN 'powerplay' " .
                    "WHEN o.over_number >= {$deathStart} THEN 'death' ELSE 'middle' END"
                ),
                DB::raw(
                    "CASE WHEN LOWER(COALESCE(batter.batting_style,'')) LIKE '%left%' " .
                    "THEN 'left' WHEN LOWER(COALESCE(batter.batting_style,'')) LIKE '%right%' " .
                    "THEN 'right' ELSE 'unknown' END"
                ),
            ])
            ->selectRaw(
                "d.bowler_id as player_id, bowler.display_name as player_name, " .
                "CASE WHEN o.over_number <= {$powerplayEnd} THEN 'powerplay' " .
                "WHEN o.over_number >= {$deathStart} THEN 'death' ELSE 'middle' END as phase, " .
                "CASE WHEN LOWER(COALESCE(batter.batting_style,'')) LIKE '%left%' THEN 'left' " .
                "WHEN LOWER(COALESCE(batter.batting_style,'')) LIKE '%right%' THEN 'right' " .
                "ELSE 'unknown' END as vs_batting_hand, " .
                "COUNT(*) FILTER (WHERE d.is_legal = true)::int as balls, " .
                "SUM(CASE WHEN COALESCE(d.extra_type, 'none') IN ('bye','leg_bye','leg bye') " .
                "THEN d.runs_off_bat ELSE d.runs_off_bat + d.extra_runs END)::int as runs_conceded, " .
                "SUM(CASE WHEN d.wicket = true AND COALESCE(LOWER(d.wicket_type), '') NOT IN " .
                "('run_out','run out','retired_hurt','retired hurt','obstructing_field','obstructing field') " .
                "THEN 1 ELSE 0 END)::int as wickets"
            )
            ->get();

        return $rows->map(function ($row) use ($evidence, $cutoff, $maxOvers) {
            $balls = (int) $row->balls;
            $economy = $balls > 0
                ? round(((int) $row->runs_conceded) / $balls * 6, 2)
                : null;

            $entity = [
                'type' => 'player',
                'id' => (int) $row->player_id,
                'name' => $row->player_name,
            ];

            $source = [
                'tables' => ['deliveries', 'overs', 'innings', 'matches', 'fixtures', 'players'],
                'cutoff' => $cutoff,
                'phase' => $row->phase,
                'vs_batting_hand' => $row->vs_batting_hand,
                'match_max_overs_context' => $maxOvers,
            ];

            return [
                'player_id' => (int) $row->player_id,
                'player_name' => $row->player_name,
                'phase' => $row->phase,
                'vs_batting_hand' => $row->vs_batting_hand,
                'balls' => $balls,
                'runs_conceded' => (int) $row->runs_conceded,
                'wickets' => (int) $row->wickets,
                'economy' => $economy,
                'balls_evidence_id' => $evidence->add(
                    'bowling_phase_balls', $balls, 'legal_balls', $balls, $entity, $source
                ),
                'wickets_evidence_id' => $evidence->add(
                    'bowling_phase_wickets', (int) $row->wickets, 'wickets', $balls, $entity, $source
                ),
                'economy_evidence_id' => $evidence->add(
                    'bowling_phase_economy', $economy, 'runs_per_6_balls', $balls, $entity, $source
                ),
                'provenance' => $source,
            ];
        })->values()->all();
    }

    private function battingPhaseStats(
        int $organizationId,
        Collection $playerIds,
        string $cutoff,
        int $maxOvers,
        EvidenceFactory $evidence
    ): array {
        if ($playerIds->isEmpty() || ! Schema::hasTable('overs')) {
            return [];
        }

        [$powerplayEnd, $deathStart] = $this->phaseBounds($maxOvers);

        $rows = DB::table('deliveries as d')
            ->join('overs as o', 'o.id', '=', 'd.over_id')
            ->join('innings as i', 'i.id', '=', 'd.innings_id')
            ->join('matches as m', 'm.id', '=', 'i.match_id')
            ->join('fixtures as f', 'f.id', '=', 'm.fixture_id')
            ->join('players as p', 'p.id', '=', 'd.batter_id')
            ->where('m.organization_id', $organizationId)
            ->whereIn('d.batter_id', $playerIds)
            ->where('f.scheduled_at', '<', $cutoff)
            ->whereRaw("LOWER(COALESCE(i.status, '')) = 'completed'")
            ->groupBy([
                'd.batter_id',
                'p.display_name',
                DB::raw(
                    "CASE WHEN o.over_number <= {$powerplayEnd} THEN 'powerplay' " .
                    "WHEN o.over_number >= {$deathStart} THEN 'death' ELSE 'middle' END"
                ),
            ])
            ->selectRaw(
                "d.batter_id as player_id, p.display_name as player_name, " .
                "CASE WHEN o.over_number <= {$powerplayEnd} THEN 'powerplay' " .
                "WHEN o.over_number >= {$deathStart} THEN 'death' ELSE 'middle' END as phase, " .
                "SUM(d.runs_off_bat)::int as runs, " .
                "COUNT(*) FILTER (WHERE d.is_legal = true)::int as balls, " .
                "COUNT(*) FILTER (WHERE d.is_legal = true AND d.total_runs = 0)::int as dots, " .
                "SUM(CASE WHEN d.runs_off_bat IN (4,6) THEN 1 ELSE 0 END)::int as boundaries"
            )
            ->get();

        return $rows->map(function ($row) use ($evidence, $cutoff, $maxOvers) {
            $balls = (int) $row->balls;
            $runs = (int) $row->runs;
            $strikeRate = $balls > 0 ? round($runs / $balls * 100, 2) : null;

            $entity = [
                'type' => 'player',
                'id' => (int) $row->player_id,
                'name' => $row->player_name,
            ];

            $source = [
                'tables' => ['deliveries', 'overs', 'innings', 'matches', 'fixtures'],
                'cutoff' => $cutoff,
                'phase' => $row->phase,
                'match_max_overs_context' => $maxOvers,
            ];

            return [
                'player_id' => (int) $row->player_id,
                'player_name' => $row->player_name,
                'phase' => $row->phase,
                'runs' => $runs,
                'balls' => $balls,
                'dots' => (int) $row->dots,
                'boundaries' => (int) $row->boundaries,
                'strike_rate' => $strikeRate,
                'balls_evidence_id' => $evidence->add(
                    'batting_phase_balls', $balls, 'legal_balls', $balls, $entity, $source
                ),
                'strike_rate_evidence_id' => $evidence->add(
                    'batting_phase_strike_rate', $strikeRate, 'runs_per_100_balls', $balls, $entity, $source
                ),
                'provenance' => $source,
            ];
        })->values()->all();
    }

    private function venueStats(
        int $organizationId,
        ?int $venueId,
        string $cutoff,
        EvidenceFactory $evidence
    ): array {
        if (! $venueId) {
            return [
                'venue_id' => null,
                'matches' => 0,
                'average_first_innings_total' => null,
                'evidence_ids' => [],
            ];
        }

        $row = DB::table('innings as i')
            ->join('matches as m', 'm.id', '=', 'i.match_id')
            ->join('fixtures as f', 'f.id', '=', 'm.fixture_id')
            ->where('m.organization_id', $organizationId)
            ->where('f.venue_id', $venueId)
            ->where('f.scheduled_at', '<', $cutoff)
            ->where('i.innings_number', 1)
            ->whereRaw("LOWER(COALESCE(i.status, '')) = 'completed'")
            ->selectRaw(
                'COUNT(DISTINCT m.id)::int as matches, ' .
                'AVG(i.runs)::numeric(10,2) as average_first_innings_total, ' .
                'MIN(i.runs)::int as minimum_first_innings_total, ' .
                'MAX(i.runs)::int as maximum_first_innings_total'
            )
            ->first();

        $matches = (int) ($row->matches ?? 0);
        $average = $row?->average_first_innings_total !== null
            ? round((float) $row->average_first_innings_total, 2)
            : null;

        $entity = [
            'type' => 'venue',
            'id' => $venueId,
        ];

        $source = [
            'tables' => ['innings', 'matches', 'fixtures'],
            'cutoff' => $cutoff,
            'venue_id' => $venueId,
            'scope' => 'completed historical first innings before match',
        ];

        return [
            'venue_id' => $venueId,
            'matches' => $matches,
            'average_first_innings_total' => $average,
            'minimum_first_innings_total' =>
                $row?->minimum_first_innings_total !== null
                    ? (int) $row->minimum_first_innings_total
                    : null,
            'maximum_first_innings_total' =>
                $row?->maximum_first_innings_total !== null
                    ? (int) $row->maximum_first_innings_total
                    : null,
            'evidence_ids' => [
                'matches' => $evidence->add(
                    'venue_historical_matches', $matches, 'matches', $matches, $entity, $source
                ),
                'average_first_innings_total' => $evidence->add(
                    'venue_average_first_innings_total', $average, 'runs', $matches, $entity, $source
                ),
            ],
        ];
    }

    private function matchups(
        int $organizationId,
        Collection $focusPlayerIds,
        Collection $opponentPlayerIds,
        string $cutoff,
        EvidenceFactory $evidence
    ): array {
        if ($focusPlayerIds->isEmpty() || $opponentPlayerIds->isEmpty()) {
            return [];
        }

        $rows = DB::table('deliveries as d')
            ->join('innings as i', 'i.id', '=', 'd.innings_id')
            ->join('matches as m', 'm.id', '=', 'i.match_id')
            ->join('fixtures as f', 'f.id', '=', 'm.fixture_id')
            ->join('players as batter', 'batter.id', '=', 'd.batter_id')
            ->join('players as bowler', 'bowler.id', '=', 'd.bowler_id')
            ->where('m.organization_id', $organizationId)
            ->where('f.scheduled_at', '<', $cutoff)
            ->whereRaw("LOWER(COALESCE(i.status, '')) = 'completed'")
            ->where(function ($query) use ($focusPlayerIds, $opponentPlayerIds) {
                $query
                    ->where(function ($nested) use ($focusPlayerIds, $opponentPlayerIds) {
                        $nested->whereIn('d.batter_id', $opponentPlayerIds)
                            ->whereIn('d.bowler_id', $focusPlayerIds);
                    })
                    ->orWhere(function ($nested) use ($focusPlayerIds, $opponentPlayerIds) {
                        $nested->whereIn('d.batter_id', $focusPlayerIds)
                            ->whereIn('d.bowler_id', $opponentPlayerIds);
                    });
            })
            ->groupBy([
                'd.batter_id',
                'batter.display_name',
                'd.bowler_id',
                'bowler.display_name',
            ])
            ->selectRaw(
                "d.batter_id, batter.display_name as batter_name, " .
                "d.bowler_id, bowler.display_name as bowler_name, " .
                "SUM(d.runs_off_bat)::int as runs, " .
                "COUNT(*) FILTER (WHERE d.is_legal = true)::int as balls, " .
                "SUM(CASE WHEN d.wicket = true AND d.dismissed_player_id = d.batter_id " .
                "AND COALESCE(LOWER(d.wicket_type), '') NOT IN ('run_out','run out','retired_hurt','retired hurt') " .
                "THEN 1 ELSE 0 END)::int as dismissals"
            )
            ->havingRaw("COUNT(*) FILTER (WHERE d.is_legal = true) >= 3 OR " .
                "SUM(CASE WHEN d.wicket = true AND d.dismissed_player_id = d.batter_id THEN 1 ELSE 0 END) >= 1")
            ->orderByDesc('dismissals')
            ->limit(40)
            ->get();

        return $rows->map(function ($row) use ($evidence, $cutoff) {
            $balls = (int) $row->balls;
            $runs = (int) $row->runs;
            $dismissals = (int) $row->dismissals;
            $strikeRate = $balls > 0 ? round($runs / $balls * 100, 2) : null;

            $entity = [
                'type' => 'matchup',
                'batter_id' => (int) $row->batter_id,
                'batter_name' => $row->batter_name,
                'bowler_id' => (int) $row->bowler_id,
                'bowler_name' => $row->bowler_name,
            ];

            $source = [
                'tables' => ['deliveries', 'innings', 'matches', 'fixtures', 'players'],
                'cutoff' => $cutoff,
                'scope' => 'direct historical batter-vs-bowler deliveries before match',
            ];

            return [
                'batter_id' => (int) $row->batter_id,
                'batter_name' => $row->batter_name,
                'bowler_id' => (int) $row->bowler_id,
                'bowler_name' => $row->bowler_name,
                'runs' => $runs,
                'balls' => $balls,
                'dismissals' => $dismissals,
                'strike_rate' => $strikeRate,
                'runs_evidence_id' => $evidence->add(
                    'matchup_runs', $runs, 'runs', $balls, $entity, $source
                ),
                'balls_evidence_id' => $evidence->add(
                    'matchup_balls', $balls, 'legal_balls', $balls, $entity, $source
                ),
                'dismissals_evidence_id' => $evidence->add(
                    'matchup_dismissals', $dismissals, 'dismissals', $balls, $entity, $source
                ),
                'strike_rate_evidence_id' => $evidence->add(
                    'matchup_strike_rate', $strikeRate, 'runs_per_100_balls', $balls, $entity, $source
                ),
                'provenance' => $source,
            ];
        })->values()->all();
    }

    private function trainingContext(
        int $organizationId,
        Collection $playerIds
    ): array {
        if ($playerIds->isEmpty()) {
            return [
                'player_assessments' => [],
                'development_plans' => [],
                'training_objectives' => [],
            ];
        }

        $assessments = Schema::hasTable('player_assessments')
            ? DB::table('player_assessments as pa')
                ->join('players as p', 'p.id', '=', 'pa.player_id')
                ->leftJoin('users as u', 'u.id', '=', 'pa.coach_id')
                ->where('pa.organization_id', $organizationId)
                ->whereIn('pa.player_id', $playerIds)
                ->orderByDesc('pa.assessed_at')
                ->limit(40)
                ->get([
                    'pa.id',
                    'pa.player_id',
                    'p.display_name as player_name',
                    'pa.assessed_at',
                    'pa.strengths',
                    'pa.weaknesses',
                    'pa.notes',
                    'u.name as coach_name',
                ])
                ->map(fn ($row) => [
                    ...((array) $row),
                    'provenance' => [
                        'table' => 'player_assessments',
                        'record_id' => (int) $row->id,
                    ],
                ])
                ->values()
                ->all()
            : [];

        $plans = Schema::hasTable('development_plans')
            ? DB::table('development_plans as dp')
                ->join('players as p', 'p.id', '=', 'dp.player_id')
                ->where('dp.organization_id', $organizationId)
                ->whereIn('dp.player_id', $playerIds)
                ->whereRaw("LOWER(COALESCE(dp.status,'')) <> 'completed'")
                ->orderByDesc('dp.updated_at')
                ->limit(30)
                ->get([
                    'dp.id',
                    'dp.player_id',
                    'p.display_name as player_name',
                    'dp.title',
                    'dp.weakness',
                    'dp.objectives',
                    'dp.start_date',
                    'dp.target_date',
                    'dp.status',
                    'dp.review_notes',
                ])
                ->map(fn ($row) => [
                    ...((array) $row),
                    'provenance' => [
                        'table' => 'development_plans',
                        'record_id' => (int) $row->id,
                    ],
                ])
                ->values()
                ->all()
            : [];

        $objectives = Schema::hasTable('training_objectives')
            ? DB::table('training_objectives as tor')
                ->join('players as p', 'p.id', '=', 'tor.player_id')
                ->where('tor.organization_id', $organizationId)
                ->whereIn('tor.player_id', $playerIds)
                ->whereRaw("LOWER(COALESCE(tor.status,'')) <> 'completed'")
                ->orderByDesc('tor.updated_at')
                ->limit(40)
                ->get([
                    'tor.id',
                    'tor.player_id',
                    'p.display_name as player_name',
                    'tor.title',
                    'tor.weakness',
                    'tor.statistic_scope',
                    'tor.metric_key',
                    'tor.status',
                    'tor.target_date',
                    'tor.notes',
                ])
                ->map(fn ($row) => [
                    ...((array) $row),
                    'provenance' => [
                        'table' => 'training_objectives',
                        'record_id' => (int) $row->id,
                    ],
                ])
                ->values()
                ->all()
            : [];

        return [
            'player_assessments' => $assessments,
            'development_plans' => $plans,
            'training_objectives' => $objectives,
        ];
    }

    private function scoutingContext(
        int $organizationId,
        Collection $playerIds
    ): array {
        if (
            $playerIds->isEmpty() ||
            ! Schema::hasTable('scouting_profiles')
        ) {
            return [
                'authorized' => true,
                'reports' => [],
                'notes' => [],
            ];
        }

        $profileQuery = DB::table('scouting_profiles')
            ->where('organization_id', $organizationId);

        $linkColumns = [];
        foreach (['existing_player_id', 'converted_player_id'] as $column) {
            if (Schema::hasColumn('scouting_profiles', $column)) {
                $linkColumns[] = $column;
            }
        }

        if ($linkColumns === []) {
            return [
                'authorized' => true,
                'reports' => [],
                'notes' => [],
                'limitation' =>
                    'Scouting profiles do not expose a player-link column that P15 can safely use.',
            ];
        }

        $profileQuery->where(function ($query) use ($linkColumns, $playerIds) {
            foreach ($linkColumns as $index => $column) {
                if ($index === 0) {
                    $query->whereIn($column, $playerIds);
                } else {
                    $query->orWhereIn($column, $playerIds);
                }
            }
        });

        $profileIds = $profileQuery->pluck('id');

        if ($profileIds->isEmpty()) {
            return [
                'authorized' => true,
                'reports' => [],
                'notes' => [],
            ];
        }

        $reports = Schema::hasTable('scouting_reports')
            ? DB::table('scouting_reports as sr')
                ->join('scouting_profiles as sp', 'sp.id', '=', 'sr.scouting_profile_id')
                ->whereIn('sr.scouting_profile_id', $profileIds)
                ->orderByDesc('sr.report_date')
                ->limit(30)
                ->get([
                    'sr.id',
                    'sr.scouting_profile_id',
                    'sp.display_name',
                    'sr.report_date',
                    'sr.competition',
                    'sr.observed_role',
                    'sr.strengths',
                    'sr.weaknesses',
                    'sr.overall_recommendation',
                    'sr.notes',
                ])
                ->map(fn ($row) => [
                    ...((array) $row),
                    'provenance' => [
                        'table' => 'scouting_reports',
                        'record_id' => (int) $row->id,
                    ],
                ])
                ->values()
                ->all()
            : [];

        $notes = Schema::hasTable('scouting_notes')
            ? DB::table('scouting_notes as sn')
                ->join('scouting_profiles as sp', 'sp.id', '=', 'sn.scouting_profile_id')
                ->leftJoin('users as u', 'u.id', '=', 'sn.author_id')
                ->whereIn('sn.scouting_profile_id', $profileIds)
                ->where('sn.is_private', false)
                ->orderByDesc('sn.created_at')
                ->limit(40)
                ->get([
                    'sn.id',
                    'sn.scouting_profile_id',
                    'sn.scouting_report_id',
                    'sp.display_name',
                    'sn.note',
                    'sn.is_private',
                    'sn.created_at',
                    'u.name as author_name',
                ])
                ->map(fn ($row) => [
                    ...((array) $row),
                    'provenance' => [
                        'table' => 'scouting_notes',
                        'record_id' => (int) $row->id,
                    ],
                ])
                ->values()
                ->all()
            : [];

        return [
            'authorized' => true,
            'reports' => $reports,
            'notes' => $notes,
        ];
    }

    private function phaseBounds(int $maxOvers): array
    {
        if ($maxOvers <= 20) {
            return [6, 16];
        }

        if ($maxOvers <= 50) {
            return [10, 41];
        }

        return [10, max(11, $maxOvers - 9)];
    }
}
