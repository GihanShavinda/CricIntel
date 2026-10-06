<?php

namespace Tests\Feature\StrategyAi;

use App\Http\Controllers\Api\V1\StrategyAiController;
use App\Models\AiStrategyRun;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class P15SmokeTest extends TestCase
{
    public function test_p15_classes_exist(): void
    {
        $this->assertTrue(class_exists(AiStrategyRun::class));
        $this->assertTrue(class_exists(StrategyAiController::class));
    }

    public function test_p15_ai_strategy_runs_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('ai_strategy_runs'));

        $this->assertTrue(
            Schema::hasColumns('ai_strategy_runs', [
                'organization_id',
                'match_id',
                'user_id',
                'question',
                'context_hash',
                'context_snapshot',
                'deterministic_recommendations',
                'raw_llm_response',
                'validated_response',
                'validation_status',
                'validation_errors',
            ])
        );
    }

    public function test_p15_routes_are_registered(): void
    {
        $uris = collect(Route::getRoutes())
            ->map(fn ($route) => $route->uri())
            ->values();

        foreach ([
            'api/v1/organizations/{organization}/strategy-assistant/status',
            'api/v1/organizations/{organization}/strategy-assistant/options',
            'api/v1/organizations/{organization}/strategy-assistant/matches/{match}/context',
            'api/v1/organizations/{organization}/strategy-assistant/matches/{match}/ask',
            'api/v1/organizations/{organization}/strategy-assistant/history',
        ] as $uri) {
            $this->assertTrue(
                $uris->contains($uri),
                "Missing P15 route: {$uri}"
            );
        }
    }

    public function test_kill_switch_is_disabled_by_default(): void
    {
        $this->assertFalse(
            (bool) config('strategy_ai.enabled')
        );
    }
}
