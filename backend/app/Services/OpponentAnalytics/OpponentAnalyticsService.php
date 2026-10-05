<?php

namespace App\Services\OpponentAnalytics;

use App\Models\Player;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OpponentAnalyticsService
{
    public function __construct(
        private readonly MatchupQueryService $queries,
        private readonly OpponentAnalyticsCalculator $calculator
    ) {}

    public function options(int $organizationId): array
    {
        $teams = DB::table('teams as t')
            ->join('clubs as c', 'c.id', '=', 't.club_id')
            ->where('c.organization_id', $organizationId)
            ->orderBy('t.name')
            ->get(['t.id', 't.name', 't.short_name']);

        $players = Player::query()
            ->where('organization_id', $organizationId)
            ->orderBy('display_name')
            ->get([
                'id',
                'display_name',
                'first_name',
                'last_name',
                'primary_role',
                'batting_style',
                'bowling_style',
            ]);

        return [
            'teams' => $teams,
            'players' => $players,
        ];
    }

    public function teamProfile(
        int $organizationId,
        int $teamId,
        array $filters = []
    ): array {
        $rows = $this->teamDeliveries($organizationId, $teamId, $filters);

        $batterIds = $rows
            ->where('batting_team_id', $teamId)
            ->pluck('batter_id')
            ->filter()
            ->unique()
            ->values();

        $bowlerIds = $rows
            ->where('bowling_team_id', $teamId)
            ->pluck('bowler_id')
            ->filter()
            ->unique()
            ->values();

        $players = Player::query()
            ->whereIn('id', $batterIds->merge($bowlerIds)->unique())
            ->get()
            ->keyBy('id');

        $batters = $batterIds
            ->map(function ($id) use ($rows, $players) {
                $player = $players->get($id);

                return $this->batterFromRows(
                    (int) $id,
                    $rows->where('batter_id', $id)->values(),
                    $player
                );
            })
            ->sortByDesc(fn ($row) => $row['summary']['runs'])
            ->values();

        $bowlers = $bowlerIds
            ->map(function ($id) use ($rows, $players) {
                $player = $players->get($id);

                return $this->bowlerFromRows(
                    (int) $id,
                    $rows->where('bowler_id', $id)->values(),
                    $player
                );
            })
            ->sortByDesc(fn ($row) => $row['summary']['wickets'])
            ->values();

        return [
            'team_id' => $teamId,
            'filters' => $filters,
            'batters' => $batters,
            'bowlers' => $bowlers,
            'partnerships' => $this->partnershipsFromRows(
                $rows->where('batting_team_id', $teamId)->values()
            ),
            'sample' => [
                'deliveries' => $rows->count(),
                'matches' => $rows->pluck('match_id')->unique()->count(),
                'message' => $rows->count() < 60
                    ? 'Team-level opponent analysis is based on fewer than 60 recorded deliveries.'
                    : 'Team-level analysis contains at least 60 recorded deliveries.',
            ],
        ];
    }

    public function batter(
        int $organizationId,
        int $playerId,
        array $filters = []
    ): array {
        $player = Player::query()
            ->where('organization_id', $organizationId)
            ->findOrFail($playerId);

        $rows = $this->filteredRows(
            $organizationId,
            $filters,
            $playerId
        )->where('batter_id', $playerId)->values();

        return $this->batterFromRows($playerId, $rows, $player);
    }

    public function bowler(
        int $organizationId,
        int $playerId,
        array $filters = []
    ): array {
        $player = Player::query()
            ->where('organization_id', $organizationId)
            ->findOrFail($playerId);

        $rows = $this->filteredRows(
            $organizationId,
            $filters,
            $playerId
        )->where('bowler_id', $playerId)->values();

        return $this->bowlerFromRows($playerId, $rows, $player);
    }

    public function matchup(
        int $organizationId,
        int $batterId,
        int $bowlerId,
        array $filters = []
    ): array {
        $batter = Player::query()
            ->where('organization_id', $organizationId)
            ->findOrFail($batterId);

        $bowler = Player::query()
            ->where('organization_id', $organizationId)
            ->findOrFail($bowlerId);

        $rows = $this->filteredRows($organizationId, $filters)
            ->where('batter_id', $batterId)
            ->where('bowler_id', $bowlerId)
            ->values();

        $balls = $rows->filter(fn ($row) => $this->isLegalBall($row))->count();
        $runs = (int) $rows->sum('runs_off_bat');
        $dismissals = $rows
            ->filter(fn ($row) =>
                (int) $row->dismissed_player_id === $batterId &&
                $this->calculator->normalizedDismissalWicket($row)
            )
            ->count();

        return [
            'batter' => [
                'id' => $batter->id,
                'name' => $batter->display_name,
                'batting_style' => $batter->batting_style,
            ],
            'bowler' => [
                'id' => $bowler->id,
                'name' => $bowler->display_name,
                'bowling_style' => $bowler->bowling_style,
            ],
            'runs' => $runs,
            'balls' => $balls,
            'strike_rate' => $this->calculator->strikeRate($runs, $balls),
            'dismissals' => $dismissals,
            'boundaries' => $rows->whereIn('runs_off_bat', [4, 6])->count(),
            'dots' => $rows
                ->filter(fn ($row) =>
                    $this->isLegalBall($row) &&
                    (int) $row->total_runs === 0
                )
                ->count(),
            'phase_split' => $this->battingPhaseSplit($rows),
            'sample_size' => $this->calculator->sampleSize($balls),
            'limitations' => $this->limitations($rows),
        ];
    }

    public function partnerships(
        int $organizationId,
        int $teamId,
        array $filters = []
    ): array {
        return $this->partnershipsFromRows(
            $this->teamDeliveries($organizationId, $teamId, $filters)
                ->where('batting_team_id', $teamId)
                ->values()
        );
    }

    private function batterFromRows(
        int $playerId,
        Collection $rows,
        ?Player $player
    ): array {
        $legal = $rows->filter(fn ($row) => $this->isLegalBall($row));
        $balls = $legal->count();
        $runs = (int) $rows->sum('runs_off_bat');
        $dots = $legal->filter(fn ($row) => (int) $row->total_runs === 0)->count();

        $dismissals = $rows
            ->filter(fn ($row) =>
                (int) $row->dismissed_player_id === $playerId &&
                (bool) $row->wicket
            );

        $bowlingSplits = [];
        foreach (['pace', 'spin', 'left_arm_pace', 'left_arm_spin', 'off_spin'] as $category) {
            $subset = $rows->filter(
                fn ($row) => $this->matchesBowlingCategory(
                    $this->calculator->bowlingCategory($row->bowler_bowling_style),
                    $category
                )
            )->values();

            $subsetLegal = $subset->filter(fn ($row) => $this->isLegalBall($row));
            $subsetRuns = (int) $subset->sum('runs_off_bat');

            $bowlingSplits[$category] = [
                'runs' => $subsetRuns,
                'balls' => $subsetLegal->count(),
                'strike_rate' => $this->calculator->strikeRate(
                    $subsetRuns,
                    $subsetLegal->count()
                ),
                'dismissals' => $subset
                    ->filter(fn ($row) =>
                        (int) $row->dismissed_player_id === $playerId &&
                        $this->calculator->normalizedDismissalWicket($row)
                    )
                    ->count(),
                'sample_size' => $this->calculator->sampleSize($subsetLegal->count()),
            ];
        }

        $zones = $rows
            ->filter(fn ($row) => filled($row->pitch_zone))
            ->groupBy(fn ($row) => (string) $row->pitch_zone)
            ->map(function (Collection $group, string $zone) {
                $legal = $group->filter(fn ($row) => $this->isLegalBall($row));
                $runs = (int) $group->sum('runs_off_bat');

                return [
                    'zone' => $zone,
                    'runs' => $runs,
                    'balls' => $legal->count(),
                    'strike_rate' => $this->calculator->strikeRate($runs, $legal->count()),
                    'boundaries' => $group->whereIn('runs_off_bat', [4, 6])->count(),
                ];
            })
            ->sortByDesc('runs')
            ->values();

        $dismissalPatterns = $dismissals
            ->groupBy(function ($row) {
                $type = $row->wicket_type ?: 'unknown';
                $category = $this->calculator->bowlingCategory($row->bowler_bowling_style);

                return $type . '|' . $category;
            })
            ->map(function (Collection $group, string $key) {
                [$type, $category] = explode('|', $key, 2);

                return [
                    'wicket_type' => $type,
                    'bowling_category' => $category,
                    'count' => $group->count(),
                ];
            })
            ->sortByDesc('count')
            ->values();

        return [
            'player' => [
                'id' => $playerId,
                'name' => $player?->display_name,
                'batting_style' => $player?->batting_style,
                'primary_role' => $player?->primary_role,
            ],
            'summary' => [
                'runs' => $runs,
                'balls' => $balls,
                'strike_rate' => $this->calculator->strikeRate($runs, $balls),
                'dot_balls' => $dots,
                'dot_ball_percentage' => $this->calculator->percentage($dots, $balls),
                'boundaries' => $rows->whereIn('runs_off_bat', [4, 6])->count(),
                'dismissals' => $dismissals->count(),
            ],
            'preferred_scoring_zones' => $zones,
            'dismissal_patterns' => $dismissalPatterns,
            'vs_bowling_type' => $bowlingSplits,
            'phase_behavior' => $this->battingPhaseSplit($rows),
            'calculated_insights' => $this->batterInsights(
                $player?->display_name ?: "Player {$playerId}",
                $bowlingSplits
            ),
            'sample_size' => $this->calculator->sampleSize($balls),
            'limitations' => $this->limitations($rows),
        ];
    }

    private function bowlerFromRows(
        int $playerId,
        Collection $rows,
        ?Player $player
    ): array {
        $legal = $rows->filter(fn ($row) => $this->isLegalBall($row));
        $legalBalls = $legal->count();
        $runsConceded = (int) $rows->sum(fn ($row) => $this->bowlerRunsConceded($row));
        $wickets = $rows
            ->filter(fn ($row) =>
                (int) $row->bowler_id === $playerId &&
                $this->calculator->normalizedDismissalWicket($row)
            )
            ->count();

        $phase = [];
        foreach (['Powerplay', 'Middle', 'Death'] as $phaseName) {
            $subset = $rows->filter(
                fn ($row) => $this->phase($row) === $phaseName
            )->values();

            $subsetLegal = $subset->filter(fn ($row) => $this->isLegalBall($row));
            $subsetRuns = (int) $subset->sum(fn ($row) => $this->bowlerRunsConceded($row));
            $subsetWickets = $subset
                ->filter(fn ($row) => $this->calculator->normalizedDismissalWicket($row))
                ->count();

            $phase[$phaseName] = [
                'balls' => $subsetLegal->count(),
                'runs_conceded' => $subsetRuns,
                'wickets' => $subsetWickets,
                'economy' => $this->calculator->economy($subsetRuns, $subsetLegal->count()),
                'wicket_rate_per_100_balls' => $this->calculator->wicketRate(
                    $subsetWickets,
                    $subsetLegal->count()
                ),
                'sample_size' => $this->calculator->sampleSize($subsetLegal->count()),
            ];
        }

        $handedness = [];
        foreach (['right', 'left'] as $hand) {
            $subset = $rows->filter(
                fn ($row) => $this->calculator->battingHand($row->batter_batting_style) === $hand
            )->values();

            $subsetLegal = $subset->filter(fn ($row) => $this->isLegalBall($row));
            $subsetRuns = (int) $subset->sum(fn ($row) => $this->bowlerRunsConceded($row));
            $subsetWickets = $subset
                ->filter(fn ($row) => $this->calculator->normalizedDismissalWicket($row))
                ->count();

            $handedness[$hand] = [
                'balls' => $subsetLegal->count(),
                'runs_conceded' => $subsetRuns,
                'wickets' => $subsetWickets,
                'economy' => $this->calculator->economy($subsetRuns, $subsetLegal->count()),
                'wicket_rate_per_100_balls' => $this->calculator->wicketRate(
                    $subsetWickets,
                    $subsetLegal->count()
                ),
                'sample_size' => $this->calculator->sampleSize($subsetLegal->count()),
            ];
        }

        $lengthLine = $rows
            ->filter(fn ($row) => filled($row->delivery_type) || filled($row->pitch_zone))
            ->groupBy(function ($row) {
                return ($row->delivery_type ?: 'unknown') . '|' . ($row->pitch_zone ?: 'unknown');
            })
            ->map(function (Collection $group, string $key) {
                [$length, $line] = explode('|', $key, 2);
                $legal = $group->filter(fn ($row) => $this->isLegalBall($row));

                return [
                    'delivery_type' => $length,
                    'pitch_zone' => $line,
                    'balls' => $legal->count(),
                    'runs_conceded' => (int) $group->sum(
                        fn ($row) => $this->bowlerRunsConceded($row)
                    ),
                    'wickets' => $group
                        ->filter(fn ($row) => $this->calculator->normalizedDismissalWicket($row))
                        ->count(),
                ];
            })
            ->sortByDesc('balls')
            ->values();

        $boundaries = $rows->whereIn('runs_off_bat', [4, 6])->count();

        return [
            'player' => [
                'id' => $playerId,
                'name' => $player?->display_name,
                'bowling_style' => $player?->bowling_style,
                'primary_role' => $player?->primary_role,
            ],
            'summary' => [
                'balls' => $legalBalls,
                'runs_conceded' => $runsConceded,
                'wickets' => $wickets,
                'economy' => $this->calculator->economy($runsConceded, $legalBalls),
                'wicket_rate_per_100_balls' => $this->calculator->wicketRate(
                    $wickets,
                    $legalBalls
                ),
                'boundaries_conceded' => $boundaries,
                'boundary_conceded_percentage' => $this->calculator->percentage(
                    $boundaries,
                    $legalBalls
                ),
            ],
            'economy_by_phase' => $phase,
            'vs_batter_handedness' => $handedness,
            'length_line_tendencies' => $lengthLine,
            'sample_size' => $this->calculator->sampleSize($legalBalls),
            'limitations' => $this->limitations($rows),
        ];
    }

    private function battingPhaseSplit(Collection $rows): array
    {
        $output = [];

        foreach (['Powerplay', 'Middle', 'Death'] as $phaseName) {
            $subset = $rows
                ->filter(fn ($row) => $this->phase($row) === $phaseName)
                ->values();

            $legal = $subset->filter(fn ($row) => $this->isLegalBall($row));
            $runs = (int) $subset->sum('runs_off_bat');
            $dots = $legal->filter(fn ($row) => (int) $row->total_runs === 0)->count();

            $output[$phaseName] = [
                'runs' => $runs,
                'balls' => $legal->count(),
                'strike_rate' => $this->calculator->strikeRate($runs, $legal->count()),
                'dot_ball_percentage' => $this->calculator->percentage(
                    $dots,
                    $legal->count()
                ),
                'boundaries' => $subset->whereIn('runs_off_bat', [4, 6])->count(),
                'sample_size' => $this->calculator->sampleSize($legal->count()),
            ];
        }

        return $output;
    }

    private function partnershipsFromRows(Collection $rows): array
    {
        $playerNames = Player::query()
            ->whereIn(
                'id',
                $rows->pluck('batter_id')
                    ->merge($rows->pluck('non_striker_id'))
                    ->filter()
                    ->unique()
            )
            ->pluck('display_name', 'id');

        $instances = [];

        foreach ($rows->groupBy('innings_id') as $inningsId => $inningsRows) {
            $current = null;

            foreach ($inningsRows as $row) {
                if (! $row->batter_id || ! $row->non_striker_id) {
                    continue;
                }

                $pair = [(int) $row->batter_id, (int) $row->non_striker_id];
                sort($pair);
                $key = implode('-', $pair);

                if ($current === null || $current['key'] !== $key) {
                    if ($current !== null) {
                        $instances[] = $current;
                    }

                    $current = [
                        'key' => $key,
                        'player_ids' => $pair,
                        'innings_id' => (int) $inningsId,
                        'match_id' => (int) $row->match_id,
                        'runs' => 0,
                        'balls' => 0,
                        'dismissal_point' => null,
                    ];
                }

                $current['runs'] += (int) $row->total_runs;

                if ($this->isLegalBall($row)) {
                    $current['balls']++;
                }

                if ((bool) $row->wicket) {
                    $current['dismissal_point'] = [
                        'over' => $row->over_number,
                        'ball' => $row->ball_number,
                        'dismissed_player_id' => $row->dismissed_player_id,
                        'wicket_type' => $row->wicket_type,
                    ];
                }
            }

            if ($current !== null) {
                $instances[] = $current;
            }
        }

        return collect($instances)
            ->groupBy('key')
            ->map(function (Collection $groups) use ($playerNames) {
                $first = $groups->first();
                $runs = (int) $groups->sum('runs');
                $balls = (int) $groups->sum('balls');

                return [
                    'player_ids' => $first['player_ids'],
                    'players' => collect($first['player_ids'])
                        ->map(fn ($id) => [
                            'id' => $id,
                            'name' => $playerNames[$id] ?? "Player {$id}",
                        ])
                        ->values(),
                    'innings_together' => $groups->count(),
                    'runs' => $runs,
                    'balls' => $balls,
                    'run_rate' => $balls > 0
                        ? round(($runs / $balls) * 6, 2)
                        : null,
                    'dismissal_points' => $groups
                        ->pluck('dismissal_point')
                        ->filter()
                        ->values(),
                ];
            })
            ->sortByDesc('innings_together')
            ->values()
            ->all();
    }

    private function filteredRows(
        int $organizationId,
        array $filters = [],
        ?int $playerId = null
    ): Collection {
        return $this->queries->deliveries(
            $organizationId,
            $playerId,
            $filters['opponent_team_id'] ?? null,
            $filters['from'] ?? null,
            $filters['to'] ?? null,
            $filters['tournament_id'] ?? null
        )->get();
    }

    private function teamDeliveries(
        int $organizationId,
        int $teamId,
        array $filters = []
    ): Collection {
        return $this->queries->deliveries(
            $organizationId,
            null,
            $teamId,
            $filters['from'] ?? null,
            $filters['to'] ?? null,
            $filters['tournament_id'] ?? null
        )->get();
    }

    private function isLegalBall(object $row): bool
    {
        return ! in_array(
            mb_strtolower((string) $row->extra_type),
            ['wide', 'no_ball', 'no ball'],
            true
        );
    }

    private function bowlerRunsConceded(object $row): int
    {
        $type = mb_strtolower((string) $row->extra_type);

        if (in_array($type, ['bye', 'leg_bye', 'leg bye'], true)) {
            return (int) $row->runs_off_bat;
        }

        return (int) $row->runs_off_bat + (int) $row->extra_runs;
    }

    private function phase(object $row): string
    {
        $over = (int) ($row->over_number ?: 1);

        return $this->calculator->phaseForOver(
            $over,
            $row->max_overs ? (int) $row->max_overs : null
        );
    }

    private function matchesBowlingCategory(string $actual, string $requested): bool
    {
        if ($requested === 'pace') {
            return in_array($actual, ['pace', 'left_arm_pace'], true);
        }

        if ($requested === 'spin') {
            return in_array($actual, ['spin', 'off_spin', 'left_arm_spin'], true);
        }

        return $actual === $requested;
    }


    private function batterInsights(string $playerName, array $splits): array
    {
        $labels = [
            'left_arm_spin' => 'left-arm spin',
            'left_arm_pace' => 'left-arm pace',
            'off_spin' => 'off-spin',
            'spin' => 'spin',
            'pace' => 'pace',
        ];

        $insights = [];

        foreach ($labels as $key => $label) {
            $dismissals = (int) data_get($splits, "{$key}.dismissals", 0);

            if ($dismissals > 0) {
                $insights[] = sprintf(
                    '%s has been dismissed %d %s against %s in the selected analysis period.',
                    $playerName,
                    $dismissals,
                    $dismissals === 1 ? 'time' : 'times',
                    $label
                );
            }
        }

        return $insights;
    }

    private function limitations(Collection $rows): array
    {
        $limitations = [];

        if ($rows->isEmpty()) {
            return ['No stored deliveries match the selected analysis period.'];
        }

        if ($rows->whereNotNull('pitch_zone')->isEmpty()) {
            $limitations[] = 'Pitch-zone analysis is unavailable because pitch_zone is not recorded in this sample.';
        }

        if ($rows->whereNotNull('delivery_type')->isEmpty()) {
            $limitations[] = 'Length/delivery-type analysis is unavailable because delivery_type is not recorded in this sample.';
        }

        if ($rows->filter(fn ($row) => filled($row->bowler_bowling_style))->isEmpty()) {
            $limitations[] = 'Bowling-type splits are unavailable because bowler bowling styles are missing.';
        }

        if ($rows->filter(fn ($row) => filled($row->batter_batting_style))->isEmpty()) {
            $limitations[] = 'Batter-handedness splits are unavailable because batting styles are missing.';
        }

        $limitations[] = 'These are descriptive historical statistics from stored deliveries, not predictions or recommendations.';

        return $limitations;
    }
}
