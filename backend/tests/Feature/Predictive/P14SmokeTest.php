<?php

namespace Tests\Feature\Predictive;

use App\Http\Controllers\Api\V1\PredictiveAnalyticsController;
use App\Services\Predictive\PredictiveAnalyticsClient;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class P14SmokeTest extends TestCase
{
    public function test_p14_backend_classes_exist(): void
    {
        $this->assertTrue(
            class_exists(PredictiveAnalyticsController::class)
        );

        $this->assertTrue(
            class_exists(PredictiveAnalyticsClient::class)
        );
    }

    public function test_p14_routes_are_registered(): void
    {
        $uris = collect(Route::getRoutes())
            ->map(fn ($route) => $route->uri())
            ->values();

        foreach ([
            'api/v1/organizations/{organization}/predictive/options',
            'api/v1/organizations/{organization}/predictive/readiness',
            'api/v1/organizations/{organization}/predictive/train',
            'api/v1/organizations/{organization}/predictive/models/{modelKind}',
            'api/v1/organizations/{organization}/predictive/batters/{player}/score',
            'api/v1/organizations/{organization}/predictive/bowlers/{player}/economy',
            'api/v1/organizations/{organization}/predictive/teams/{team}/total',
            'api/v1/organizations/{organization}/predictive/players/{player}/form',
        ] as $uri) {
            $this->assertTrue(
                $uris->contains($uri),
                "Missing P14 route: {$uri}"
            );
        }
    }

    public function test_predictive_configuration_has_local_service_defaults(): void
    {
        $this->assertSame(
            'http://127.0.0.1:8100',
            config('predictive.base_url')
        );

        $this->assertTrue(
            (bool) config('predictive.enabled')
        );
    }
}
