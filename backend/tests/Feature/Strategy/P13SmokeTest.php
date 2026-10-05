<?php

namespace Tests\Feature\Strategy;

use App\Http\Controllers\Api\V1\StrategyCollaborationController;
use App\Http\Controllers\Api\V1\StrategyPlanController;
use App\Models\Mention;
use App\Models\StrategyAssignment;
use App\Models\StrategyAttachment;
use App\Models\StrategyComment;
use App\Models\StrategyPlan;
use App\Models\StrategySection;
use App\Models\StrategyVersion;
use App\Models\TacticalNote;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class P13SmokeTest extends TestCase
{
    public function test_p13_models_and_controllers_exist(): void
    {
        foreach ([
            StrategyPlan::class,
            StrategySection::class,
            TacticalNote::class,
            StrategyComment::class,
            Mention::class,
            StrategyAttachment::class,
            StrategyAssignment::class,
            StrategyVersion::class,
            StrategyPlanController::class,
            StrategyCollaborationController::class,
        ] as $class) {
            $this->assertTrue(
                class_exists($class),
                "Missing P13 class: {$class}"
            );
        }
    }

    public function test_p13_tables_exist(): void
    {
        foreach ([
            'strategy_plans',
            'strategy_sections',
            'tactical_notes',
            'comments',
            'mentions',
            'attachments',
            'strategy_assignments',
            'strategy_versions',
        ] as $table) {
            $this->assertTrue(
                Schema::hasTable($table),
                "Missing P13 table: {$table}"
            );
        }
    }

    public function test_p13_core_columns_exist(): void
    {
        $this->assertTrue(Schema::hasColumns('strategy_plans', [
            'organization_id',
            'match_id',
            'opponent_team_id',
            'venue_id',
            'title',
            'status',
            'summary',
            'locked_at',
            'deleted_at',
        ]));

        $this->assertTrue(Schema::hasColumns('strategy_sections', [
            'strategy_plan_id',
            'section_key',
            'title',
            'content',
            'structured_data',
            'sort_order',
        ]));

        $this->assertTrue(Schema::hasColumns('tactical_notes', [
            'strategy_plan_id',
            'strategy_section_id',
            'author_id',
            'body',
            'status',
            'resolved_at',
        ]));

        $this->assertTrue(Schema::hasColumns('strategy_versions', [
            'strategy_plan_id',
            'version_number',
            'event_type',
            'entity_type',
            'change_summary',
            'snapshot',
            'changes',
        ]));
    }

    public function test_p13_routes_are_registered(): void
    {
        $uris = collect(Route::getRoutes())
            ->map(fn ($route) => $route->uri())
            ->values();

        foreach ([
            'api/v1/organizations/{organization}/strategy/options',
            'api/v1/organizations/{organization}/strategy/plans',
            'api/v1/organizations/{organization}/strategy/plans/{strategyPlan}',
            'api/v1/organizations/{organization}/strategy/plans/{strategyPlan}/sections/{strategySection}',
            'api/v1/organizations/{organization}/strategy/plans/{strategyPlan}/versions',
            'api/v1/organizations/{organization}/strategy/plans/{strategyPlan}/lock',
            'api/v1/organizations/{organization}/strategy/plans/{strategyPlan}/unlock',
            'api/v1/organizations/{organization}/strategy/plans/{strategyPlan}/notes',
            'api/v1/organizations/{organization}/strategy/plans/{strategyPlan}/notes/{tacticalNote}/comments',
            'api/v1/organizations/{organization}/strategy/plans/{strategyPlan}/attachments',
            'api/v1/organizations/{organization}/strategy/plans/{strategyPlan}/assignments',
            'api/v1/organizations/{organization}/strategy/plans/{strategyPlan}/mentions/{mention}/read',
        ] as $uri) {
            $this->assertTrue(
                $uris->contains($uri),
                "Missing P13 route: {$uri}"
            );
        }
    }
}
