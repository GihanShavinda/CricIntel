<?php

namespace Tests\Feature\OpponentAnalytics;

use App\Http\Controllers\Api\V1\OpponentAnalyticsController;
use App\Services\OpponentAnalytics\MatchupQueryService;
use App\Services\OpponentAnalytics\OpponentAnalyticsCalculator;
use App\Services\OpponentAnalytics\OpponentAnalyticsService;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class P12SmokeTest extends TestCase
{
    public function test_p12_classes_exist(): void
    {
        $this->assertTrue(class_exists(OpponentAnalyticsController::class));
        $this->assertTrue(class_exists(OpponentAnalyticsService::class));
        $this->assertTrue(class_exists(MatchupQueryService::class));
        $this->assertTrue(class_exists(OpponentAnalyticsCalculator::class));
    }

    public function test_p12_routes_are_registered(): void
    {
        $uris = collect(Route::getRoutes())
            ->map(fn ($route) => $route->uri())
            ->values();

        foreach ([
            'api/v1/organizations/{organization}/opponent-analytics/options',
            'api/v1/organizations/{organization}/opponent-analytics/teams/{team}',
            'api/v1/organizations/{organization}/opponent-analytics/batters/{player}',
            'api/v1/organizations/{organization}/opponent-analytics/bowlers/{player}',
            'api/v1/organizations/{organization}/opponent-analytics/matchup',
            'api/v1/organizations/{organization}/opponent-analytics/teams/{team}/partnerships',
        ] as $uri) {
            $this->assertTrue(
                $uris->contains($uri),
                "Missing P12 route: {$uri}"
            );
        }
    }
}
