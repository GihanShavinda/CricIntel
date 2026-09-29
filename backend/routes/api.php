<?php

use App\Http\Controllers\Api\V1\AdminController;
use App\Http\Controllers\Api\V1\AnalyticsController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ClubController;
use App\Http\Controllers\Api\V1\FixtureController;
use App\Http\Controllers\Api\V1\LiveMatchController;
use App\Http\Controllers\Api\V1\MatchController;
use App\Http\Controllers\Api\V1\OrganizationController;
use App\Http\Controllers\Api\V1\PlayerController;
use App\Http\Controllers\Api\V1\SeasonController;
use App\Http\Controllers\Api\V1\SelectionController;
use App\Http\Controllers\Api\V1\StatisticsController;
use App\Http\Controllers\Api\V1\TeamController;
use App\Http\Controllers\Api\V1\TournamentController;
use App\Http\Controllers\Api\V1\VenueController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    /*
    |--------------------------------------------------------------------------
    | P1 - Authentication
    |--------------------------------------------------------------------------
    */
    Route::prefix('auth')->group(function () {
        Route::middleware('guest')->group(function () {
            Route::post('/register', [AuthController::class, 'register']);
            Route::post('/login', [AuthController::class, 'login']);
        });

        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Authenticated API
    |--------------------------------------------------------------------------
    */
    Route::middleware('auth:sanctum')->group(function () {
        /*
        |--------------------------------------------------------------------------
        | P2 - Organization Management
        |--------------------------------------------------------------------------
        */
        Route::apiResource('organizations', OrganizationController::class);
        Route::apiResource('organizations.clubs', ClubController::class);
        Route::apiResource('organizations.teams', TeamController::class);
        Route::apiResource('organizations.seasons', SeasonController::class);

        Route::post(
            '/organizations/{organization}/seasons/{season}/teams/sync',
            [SeasonController::class, 'syncTeams']
        );

        /*
        |--------------------------------------------------------------------------
        | P3 - Player Management
        |--------------------------------------------------------------------------
        */
        Route::apiResource('organizations.players', PlayerController::class);

        Route::post(
            '/organizations/{organization}/players/{player}/teams/sync',
            [PlayerController::class, 'syncTeams']
        );

        Route::post(
            '/organizations/{organization}/players/{player}/availability',
            [PlayerController::class, 'storeAvailability']
        );

        Route::delete(
            '/organizations/{organization}/players/{player}/availability/{availability}',
            [PlayerController::class, 'destroyAvailability']
        );

        /*
        |--------------------------------------------------------------------------
        | P4 - Competition Management
        |--------------------------------------------------------------------------
        */
        Route::apiResource('organizations.venues', VenueController::class);
        Route::apiResource('organizations.tournaments', TournamentController::class);
        Route::apiResource('organizations.fixtures', FixtureController::class);

        Route::post(
            '/organizations/{organization}/tournaments/{tournament}/teams/sync',
            [TournamentController::class, 'syncTeams']
        );

        /*
        |--------------------------------------------------------------------------
        | P5 - Match Scoring
        |--------------------------------------------------------------------------
        */
        Route::get(
            '/organizations/{organization}/matches',
            [MatchController::class, 'index']
        );

        Route::post(
            '/organizations/{organization}/matches',
            [MatchController::class, 'store']
        );

        Route::post(
            '/organizations/{organization}/matches/{match}/start',
            [MatchController::class, 'start']
        );

        Route::post(
            '/organizations/{organization}/matches/{match}/innings',
            [MatchController::class, 'startInnings']
        );

        Route::post(
            '/organizations/{organization}/matches/{match}/innings/{innings}/deliveries',
            [MatchController::class, 'recordDelivery']
        );

        Route::delete(
            '/organizations/{organization}/matches/{match}/innings/{innings}/deliveries/latest',
            [MatchController::class, 'undoDelivery']
        );

        Route::post(
            '/organizations/{organization}/matches/{match}/innings/{innings}/complete',
            [MatchController::class, 'completeInnings']
        );

        Route::post(
            '/organizations/{organization}/matches/{match}/complete',
            [MatchController::class, 'completeMatch']
        );

        Route::get(
            '/organizations/{organization}/matches/{match}/scorecard',
            [MatchController::class, 'scorecard']
        );

        /*
        |--------------------------------------------------------------------------
        | P6 - Deterministic Statistics Engine
        |--------------------------------------------------------------------------
        */
        Route::prefix('organizations/{organization}/statistics')->group(function () {
            Route::get(
                '/players/{player}',
                [StatisticsController::class, 'player']
            );

            Route::get(
                '/players/{player}/batting',
                [StatisticsController::class, 'batting']
            );

            Route::get(
                '/players/{player}/bowling',
                [StatisticsController::class, 'bowling']
            );

            Route::get(
                '/players/{player}/fielding',
                [StatisticsController::class, 'fielding']
            );

            Route::get(
                '/teams/{team}',
                [StatisticsController::class, 'team']
            );

            Route::get(
                '/matches/{match}',
                [StatisticsController::class, 'match']
            );

            Route::get(
                '/matches/{match}/phases',
                [StatisticsController::class, 'phases']
            );
        });

        /*
        |--------------------------------------------------------------------------
        | P7 - Analytics
        |--------------------------------------------------------------------------
        */
        Route::prefix('organizations/{organization}/analytics')->group(function () {
            Route::get(
                '/options',
                [AnalyticsController::class, 'options']
            );

            Route::get(
                '/dashboard',
                [AnalyticsController::class, 'dashboard']
            );

            Route::get(
                '/players/compare',
                [AnalyticsController::class, 'compare']
            );
        });

        /*
        |--------------------------------------------------------------------------
        | P8 - Live Match Centre
        |--------------------------------------------------------------------------
        */
        Route::get(
            '/organizations/{organization}/matches/{match}/live',
            [LiveMatchController::class, 'show']
        );

        /*
        |--------------------------------------------------------------------------
        | P9 - Tournament Squad
        |--------------------------------------------------------------------------
        */
        Route::get(
            '/organizations/{organization}/tournaments/{tournament}/teams/{team}/squad',
            [SelectionController::class, 'getTournamentSquad']
        );

        Route::post(
            '/organizations/{organization}/tournaments/{tournament}/teams/{team}/squad',
            [SelectionController::class, 'createTournamentSquad']
        );

        Route::put(
            '/organizations/{organization}/squads/{squad}/players',
            [SelectionController::class, 'syncTournamentPlayers']
        );

        Route::post(
            '/organizations/{organization}/squads/{squad}/finalize',
            [SelectionController::class, 'finalizeTournamentSquad']
        );

        /*
        |--------------------------------------------------------------------------
        | P9 - Match Squad
        |--------------------------------------------------------------------------
        */
        Route::get(
            '/organizations/{organization}/matches/{match}/teams/{team}/squad',
            [SelectionController::class, 'getMatchSquad']
        );

        Route::post(
            '/organizations/{organization}/matches/{match}/teams/{team}/squad',
            [SelectionController::class, 'saveMatchSquad']
        );

        Route::get(
            '/organizations/{organization}/matches/{match}/teams/{team}/selection/candidates',
            [SelectionController::class, 'candidates']
        );

        /*
        |--------------------------------------------------------------------------
        | P9 - Playing XI
        |--------------------------------------------------------------------------
        */
        Route::put(
            '/organizations/{organization}/matches/{match}/teams/{team}/playing-xi',
            [SelectionController::class, 'savePlayingXi']
        );

        Route::post(
            '/organizations/{organization}/matches/{match}/teams/{team}/playing-xi/confirm',
            [SelectionController::class, 'confirmPlayingXi']
        );

        /*
        |--------------------------------------------------------------------------
        | P9 - Batting Order
        |--------------------------------------------------------------------------
        */
        Route::put(
            '/organizations/{organization}/matches/{match}/teams/{team}/batting-order',
            [SelectionController::class, 'saveBattingOrder']
        );

        /*
        |--------------------------------------------------------------------------
        | P9 - Bowling Assignments
        |--------------------------------------------------------------------------
        */
        Route::put(
            '/organizations/{organization}/matches/{match}/teams/{team}/bowling-assignments',
            [SelectionController::class, 'saveBowlingAssignments']
        );

        /*
        |--------------------------------------------------------------------------
        | P9 - Selection Decisions
        |--------------------------------------------------------------------------
        */
        Route::get(
            '/organizations/{organization}/matches/{match}/teams/{team}/selection-decisions',
            [SelectionController::class, 'decisions']
        );

        Route::post(
            '/organizations/{organization}/matches/{match}/teams/{team}/selection-decisions',
            [SelectionController::class, 'saveDecision']
        );
    });

    /*
    |--------------------------------------------------------------------------
    | Administration
    |--------------------------------------------------------------------------
    */
    Route::middleware([
        'auth:sanctum',
        'can:access-administration',
    ])->group(function () {
        Route::get(
            '/admin/ping',
            [AdminController::class, 'ping']
        );
    });
});
