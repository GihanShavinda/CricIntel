<?php

namespace App\Services\NlAnalytics;

use App\Models\Organization;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class AnalyticsEntityResolver
{
    public function resolve(
        Organization $organization,
        string $naturalLanguage,
        array $schema,
        array $explicit = []
    ): array {
        $query = mb_strtolower($naturalLanguage);

        $schema['team_id'] = $this->explicitInteger(
            $explicit['team_id'] ?? null
        );
        $schema['season_id'] = $this->explicitInteger(
            $explicit['season_id'] ?? null
        );
        $schema['opponent_team_id'] = $this->explicitInteger(
            $explicit['opponent_team_id'] ?? null
        );
        $schema['venue_id'] = $this->explicitInteger(
            $explicit['venue_id'] ?? null
        );

        $schema['date_from'] =
            $explicit['date_from'] ?? $schema['date_from'];
        $schema['date_to'] =
            $explicit['date_to'] ?? $schema['date_to'];
        $schema['format'] =
            $explicit['format'] ?? $schema['format'];

        $teams = DB::table('teams as t')
            ->join('clubs as c', 'c.id', '=', 't.club_id')
            ->where('c.organization_id', $organization->id)
            ->orderByRaw('LENGTH(t.name) DESC')
            ->get([
                't.id',
                't.name',
                't.short_name',
            ]);

        if (! $schema['team_id']) {
            foreach ($teams as $team) {
                if ($this->containsName($query, $team->name)) {
                    $schema['team_id'] = (int) $team->id;
                    $schema['team_name'] = $team->name;
                    break;
                }
            }
        }

        if (
            ! $schema['team_id'] &&
            preg_match('/\b(our|we|us)\b/i', $naturalLanguage) &&
            $teams->count() === 1
        ) {
            $schema['team_id'] = (int) $teams->first()->id;
            $schema['team_name'] = $teams->first()->name;
        }

        if ($schema['team_id'] && ! $schema['team_name']) {
            $team = $teams->firstWhere('id', $schema['team_id']);
            $schema['team_name'] = $team?->name;
        }

        if (! $schema['opponent_team_id']) {
            foreach ($teams as $team) {
                if (
                    (int) $team->id === (int) $schema['team_id']
                ) {
                    continue;
                }

                $lowerName = mb_strtolower((string) $team->name);

                if (
                    str_contains($query, 'against ' . $lowerName) ||
                    str_contains($query, 'versus ' . $lowerName) ||
                    str_contains($query, 'vs ' . $lowerName)
                ) {
                    $schema['opponent_team_id'] = (int) $team->id;
                    $schema['opponent_name'] = $team->name;
                    break;
                }
            }
        }

        if (
            ! $schema['opponent_team_id'] &&
            preg_match('/\b(opposition|opponent|their)\b/i', $naturalLanguage) &&
            $teams->count() === 2 &&
            $schema['team_id']
        ) {
            $opponent = $teams->first(
                fn ($team) =>
                    (int) $team->id !== (int) $schema['team_id']
            );

            if ($opponent) {
                $schema['opponent_team_id'] = (int) $opponent->id;
                $schema['opponent_name'] = $opponent->name;
            }
        }

        if ($schema['opponent_team_id'] && ! $schema['opponent_name']) {
            $opponent = $teams->firstWhere(
                'id',
                $schema['opponent_team_id']
            );

            $schema['opponent_name'] = $opponent?->name;
        }

        if (Schema::hasTable('seasons')) {
            $seasons = DB::table('seasons')
                ->where('organization_id', $organization->id)
                ->orderByDesc('start_date')
                ->get([
                    'id',
                    'name',
                    'start_date',
                    'end_date',
                    'status',
                ]);

            if (
                ! $schema['season_id'] &&
                ($schema['season_name'] ?? null) === '__CURRENT__'
            ) {
                $season = $seasons->first(
                    fn ($row) =>
                        mb_strtolower((string) $row->status) === 'active'
                ) ?? $seasons->first();

                if ($season) {
                    $schema['season_id'] = (int) $season->id;
                    $schema['season_name'] = $season->name;
                }
            }

            if (! $schema['season_id']) {
                foreach ($seasons as $season) {
                    if ($this->containsName($query, $season->name)) {
                        $schema['season_id'] = (int) $season->id;
                        $schema['season_name'] = $season->name;
                        break;
                    }
                }
            }

            if ($schema['season_id'] && ! $schema['season_name']) {
                $season = $seasons->firstWhere(
                    'id',
                    $schema['season_id']
                );
                $schema['season_name'] = $season?->name;
            }
        }

        $venues = DB::table('venues')
            ->where('organization_id', $organization->id)
            ->orderByRaw('LENGTH(name) DESC')
            ->get([
                'id',
                'name',
                'city',
                'country',
            ]);

        if (! $schema['venue_id']) {
            foreach ($venues as $venue) {
                if ($this->containsName($query, $venue->name)) {
                    $schema['venue_id'] = (int) $venue->id;
                    $schema['venue_name'] = $venue->name;
                    break;
                }
            }
        }

        if ($schema['venue_id'] && ! $schema['venue_name']) {
            $venue = $venues->firstWhere('id', $schema['venue_id']);
            $schema['venue_name'] = $venue?->name;
        }

        $players = DB::table('players')
            ->where('organization_id', $organization->id)
            ->orderByRaw('LENGTH(display_name) DESC')
            ->get([
                'id',
                'display_name',
                'primary_role',
            ]);

        $mentionedPlayers = $players
            ->filter(
                fn ($player) =>
                    $this->containsName(
                        $query,
                        $player->display_name
                    )
            )
            ->values();

        if (
            $schema['intent'] === 'matchup_summary' &&
            $mentionedPlayers->count() >= 2
        ) {
            $first = $mentionedPlayers[0];
            $second = $mentionedPlayers[1];

            $firstRole = mb_strtolower(
                (string) $first->primary_role
            );
            $secondRole = mb_strtolower(
                (string) $second->primary_role
            );

            if (
                str_contains($firstRole, 'bowl') &&
                ! str_contains($secondRole, 'bowl')
            ) {
                [$first, $second] = [$second, $first];
            }

            $schema['batter_id'] = (int) $first->id;
            $schema['batter_name'] = $first->display_name;
            $schema['bowler_id'] = (int) $second->id;
            $schema['bowler_name'] = $second->display_name;
        } elseif ($mentionedPlayers->isNotEmpty()) {
            $player = $mentionedPlayers->first();

            $schema['player_id'] = (int) $player->id;
            $schema['player_name'] = $player->display_name;
        }

        return $schema;
    }

    public function options(Organization $organization): array
    {
        return [
            'teams' => DB::table('teams as t')
                ->join('clubs as c', 'c.id', '=', 't.club_id')
                ->where('c.organization_id', $organization->id)
                ->orderBy('t.name')
                ->get([
                    't.id',
                    't.name',
                    't.short_name',
                ]),

            'seasons' => Schema::hasTable('seasons')
                ? DB::table('seasons')
                    ->where('organization_id', $organization->id)
                    ->orderByDesc('start_date')
                    ->get([
                        'id',
                        'name',
                        'start_date',
                        'end_date',
                        'status',
                    ])
                : collect(),

            'venues' => DB::table('venues')
                ->where('organization_id', $organization->id)
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'city',
                    'country',
                ]),

            'formats' => Schema::hasTable('tournaments')
                ? DB::table('tournaments')
                    ->where('organization_id', $organization->id)
                    ->whereNotNull('format')
                    ->distinct()
                    ->orderBy('format')
                    ->pluck('format')
                    ->values()
                : collect(),

            'players' => DB::table('players')
                ->where('organization_id', $organization->id)
                ->orderBy('display_name')
                ->get([
                    'id',
                    'display_name',
                    'primary_role',
                ]),
        ];
    }

    private function explicitInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function containsName(string $query, ?string $name): bool
    {
        if (! $name) {
            return false;
        }

        return str_contains(
            $query,
            mb_strtolower(trim($name))
        );
    }
}
