<?php

namespace App\Services\NlAnalytics;

use App\Models\Organization;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ControlledAnalyticsValidator
{
    private const INTENT_RULES = [
        'rank_bowlers_phase_vs_hand' => [
            'entity' => ['bowler'],
            'metrics' => ['economy', 'wickets', 'wicket_rate', 'dot_ball_pct'],
            'requires' => ['team_id', 'phase'],
        ],
        'team_run_rate_trend' => [
            'entity' => ['team'],
            'metrics' => ['run_rate'],
            'requires' => ['team_id'],
        ],
        'batter_phase_performance' => [
            'entity' => ['batter'],
            'metrics' => ['strike_rate', 'runs', 'dot_ball_pct', 'boundary_pct'],
            'requires' => ['team_id', 'phase'],
        ],
        'bowler_phase_performance' => [
            'entity' => ['bowler'],
            'metrics' => ['economy', 'wickets', 'wicket_rate', 'dot_ball_pct'],
            'requires' => ['phase'],
        ],
        'player_form_trend' => [
            'entity' => ['player'],
            'metrics' => ['runs', 'strike_rate', 'economy', 'wickets'],
            'requires' => ['player_id'],
        ],
        'team_phase_scoring' => [
            'entity' => ['team'],
            'metrics' => ['run_rate', 'runs', 'wickets'],
            'requires' => ['team_id'],
        ],
        'matchup_summary' => [
            'entity' => ['matchup'],
            'metrics' => ['runs', 'balls', 'strike_rate', 'dismissals'],
            'requires' => ['batter_id', 'bowler_id'],
        ],
        'venue_scoring_summary' => [
            'entity' => ['venue'],
            'metrics' => ['first_innings_total'],
            'requires' => ['venue_id'],
        ],
        'opponent_phase_threats' => [
            'entity' => ['batter'],
            'metrics' => ['strike_rate', 'runs', 'boundary_pct'],
            'requires' => ['opponent_team_id', 'phase'],
        ],
    ];

    public function __construct(
        private readonly ControlledAnalyticsSchema $schema
    ) {}

    public function validate(
        Organization $organization,
        array $query
    ): array {
        $intent = $query['intent'] ?? null;

        if (! in_array(
            $intent,
            $this->schema->supportedIntents(),
            true
        )) {
            $this->fail(
                'intent',
                'Unsupported analytics intent.'
            );
        }

        $rules = self::INTENT_RULES[$intent] ?? null;

        if (! $rules) {
            $this->fail(
                'intent',
                'No controlled execution rule exists for this intent.'
            );
        }

        if (! in_array(
            $query['metric'] ?? null,
            $rules['metrics'],
            true
        )) {
            $this->fail(
                'metric',
                'This metric is not allowed for the selected analytics intent.'
            );
        }

        if (! in_array(
            $query['entity'] ?? null,
            $rules['entity'],
            true
        )) {
            $this->fail(
                'entity',
                'This entity type is not allowed for the selected analytics intent.'
            );
        }

        if (! in_array(
            $query['phase'] ?? 'all',
            $this->schema->supportedPhases(),
            true
        )) {
            $this->fail('phase', 'Unsupported cricket phase.');
        }

        if (! in_array(
            $query['batting_hand'] ?? 'all',
            $this->schema->supportedBattingHands(),
            true
        )) {
            $this->fail(
                'batting_hand',
                'Unsupported batting-hand filter.'
            );
        }

        foreach ($rules['requires'] as $required) {
            if (
                ! isset($query[$required]) ||
                $query[$required] === null ||
                $query[$required] === ''
            ) {
                $message = match ($required) {
                    'team_id' =>
                        'Select a team or include a unique team name in the request.',
                    'opponent_team_id' =>
                        'Select the opponent team or include its name in the request.',
                    'venue_id' =>
                        'Select a venue or include its name in the request.',
                    'player_id' =>
                        'Include a CricIntel player name in the request.',
                    'batter_id', 'bowler_id' =>
                        'A matchup request must identify both a batter and a bowler.',
                    'phase' =>
                        'Specify powerplay, middle overs or death overs.',
                    default =>
                        "The {$required} filter is required.",
                };

                $this->fail($required, $message);
            }
        }

        $this->validateOrganizationEntities(
            $organization,
            $query
        );

        if (
            ($query['date_from'] ?? null) &&
            ($query['date_to'] ?? null) &&
            $query['date_from'] > $query['date_to']
        ) {
            $this->fail(
                'date_range',
                'The start date must be before or equal to the end date.'
            );
        }

        $max = config('nl_analytics.max_rows', 50);

        $query['limit'] = max(
            1,
            min(
                (int) ($query['limit'] ?? 10),
                $max
            )
        );

        if (! in_array(
            $query['sort_direction'] ?? 'desc',
            config('nl_analytics.sort_directions', ['asc', 'desc']),
            true
        )) {
            $this->fail(
                'sort_direction',
                'Unsupported sort direction.'
            );
        }

        return $query;
    }

    private function validateOrganizationEntities(
        Organization $organization,
        array $query
    ): void {
        foreach (
            [
                'team_id',
                'opponent_team_id',
            ] as $key
        ) {
            if (! empty($query[$key])) {
                $exists = DB::table('teams as t')
                    ->join('clubs as c', 'c.id', '=', 't.club_id')
                    ->where('c.organization_id', $organization->id)
                    ->where('t.id', $query[$key])
                    ->exists();

                if (! $exists) {
                    $this->fail(
                        $key,
                        'The selected team does not belong to this organization.'
                    );
                }
            }
        }

        if (! empty($query['season_id'])) {
            if (
                ! Schema::hasTable('seasons') ||
                ! DB::table('seasons')
                    ->where('organization_id', $organization->id)
                    ->where('id', $query['season_id'])
                    ->exists()
            ) {
                $this->fail(
                    'season_id',
                    'The selected season does not belong to this organization.'
                );
            }
        }

        if (! empty($query['venue_id'])) {
            $exists = DB::table('venues')
                ->where('organization_id', $organization->id)
                ->where('id', $query['venue_id'])
                ->exists();

            if (! $exists) {
                $this->fail(
                    'venue_id',
                    'The selected venue does not belong to this organization.'
                );
            }
        }

        foreach (
            [
                'player_id',
                'batter_id',
                'bowler_id',
            ] as $key
        ) {
            if (! empty($query[$key])) {
                $exists = DB::table('players')
                    ->where('organization_id', $organization->id)
                    ->where('id', $query[$key])
                    ->exists();

                if (! $exists) {
                    $this->fail(
                        $key,
                        'The selected player does not belong to this organization.'
                    );
                }
            }
        }

        if (! empty($query['format'])) {
            $format = mb_strtolower(
                trim((string) $query['format'])
            );

            $allowed = collect([
                't20',
                't20i',
                'odi',
                'test',
                't10',
                'hundred',
            ])->contains($format);

            if (! $allowed) {
                $this->fail(
                    'format',
                    'Unsupported cricket format filter.'
                );
            }
        }
    }

    private function fail(
        string $key,
        string $message
    ): never {
        throw ValidationException::withMessages([
            $key => $message,
        ]);
    }
}
