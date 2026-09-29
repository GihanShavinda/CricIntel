<?php

namespace Tests\Feature\Selection;

use App\Models\BattingOrder;
use App\Models\BowlingAssignment;
use App\Models\MatchSquad;
use App\Models\MatchSquadPlayer;
use App\Models\PlayingXi;
use App\Models\SelectionDecision;
use App\Models\Squad;
use App\Models\SquadPlayer;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class P9SmokeTest extends TestCase
{
    public function test_p9_models_exist(): void
    {
        $this->assertTrue(
            class_exists(Squad::class)
        );

        $this->assertTrue(
            class_exists(SquadPlayer::class)
        );

        $this->assertTrue(
            class_exists(MatchSquad::class)
        );

        $this->assertTrue(
            class_exists(MatchSquadPlayer::class)
        );

        $this->assertTrue(
            class_exists(PlayingXi::class)
        );

        $this->assertTrue(
            class_exists(BattingOrder::class)
        );

        $this->assertTrue(
            class_exists(BowlingAssignment::class)
        );

        $this->assertTrue(
            class_exists(SelectionDecision::class)
        );
    }

    public function test_p9_tables_exist_after_migration(): void
    {
        $tables = [
            'squads',
            'squad_players',
            'match_squads',
            'match_squad_players',
            'playing_xi',
            'batting_orders',
            'bowling_assignments',
            'selection_decisions',
        ];

        foreach ($tables as $table) {
            $this->assertTrue(
                Schema::hasTable($table),
                "Missing {$table}"
            );
        }
    }

    public function test_p9_routes_are_registered(): void
    {
        $routes = collect(
            Route::getRoutes()
        );

        $uris = $routes
            ->map(
                fn ($route) =>
                    $route->uri()
            )
            ->values();

        $expectedRoutes = [
            'api/v1/organizations/{organization}/tournaments/{tournament}/teams/{team}/squad',

            'api/v1/organizations/{organization}/squads/{squad}/players',

            'api/v1/organizations/{organization}/squads/{squad}/finalize',

            'api/v1/organizations/{organization}/matches/{match}/teams/{team}/squad',

            'api/v1/organizations/{organization}/matches/{match}/teams/{team}/selection/candidates',

            'api/v1/organizations/{organization}/matches/{match}/teams/{team}/playing-xi',

            'api/v1/organizations/{organization}/matches/{match}/teams/{team}/playing-xi/confirm',

            'api/v1/organizations/{organization}/matches/{match}/teams/{team}/batting-order',

            'api/v1/organizations/{organization}/matches/{match}/teams/{team}/bowling-assignments',

            'api/v1/organizations/{organization}/matches/{match}/teams/{team}/selection-decisions',
        ];

        foreach (
            $expectedRoutes as $expectedRoute
        ) {
            $this->assertTrue(
                $uris->contains(
                    $expectedRoute
                ),
                "Missing route: {$expectedRoute}"
            );
        }
    }

    public function test_p9_core_tables_have_expected_columns(): void
    {
        $this->assertTrue(
            Schema::hasColumns(
                'squads',
                [
                    'id',
                    'organization_id',
                    'tournament_id',
                    'team_id',
                    'name',
                    'min_players',
                    'max_players',
                    'status',
                    'created_by',
                ]
            )
        );

        $this->assertTrue(
            Schema::hasColumns(
                'match_squads',
                [
                    'id',
                    'organization_id',
                    'match_id',
                    'team_id',
                    'squad_id',
                    'status',
                    'created_by',
                ]
            )
        );

        $this->assertTrue(
            Schema::hasColumns(
                'playing_xi',
                [
                    'id',
                    'match_squad_id',
                    'player_id',
                    'is_captain',
                    'is_wicketkeeper',
                    'selected_by',
                ]
            )
        );

        $this->assertTrue(
            Schema::hasColumns(
                'batting_orders',
                [
                    'id',
                    'match_squad_id',
                    'player_id',
                    'position',
                ]
            )
        );

        $this->assertTrue(
            Schema::hasColumns(
                'bowling_assignments',
                [
                    'id',
                    'match_squad_id',
                    'player_id',
                    'phase',
                    'priority',
                    'notes',
                ]
            )
        );

        $this->assertTrue(
            Schema::hasColumns(
                'selection_decisions',
                [
                    'id',
                    'organization_id',
                    'match_id',
                    'team_id',
                    'player_id',
                    'decision_type',
                    'decision',
                    'reason',
                    'context',
                    'override_used',
                    'created_by',
                ]
            )
        );
    }
}
