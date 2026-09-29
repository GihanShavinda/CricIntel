<?php

namespace Tests\Feature\Training;

use App\Models\Attendance;
use App\Models\DevelopmentPlan;
use App\Models\FitnessTest;
use App\Models\PlayerAssessment;
use App\Models\TrainingDrill;
use App\Models\TrainingObjective;
use App\Models\TrainingSession;
use App\Models\TrainingSessionPlayer;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class P10SmokeTest extends TestCase
{
    public function test_p10_models_exist(): void
    {
        $this->assertTrue(class_exists(TrainingSession::class));
        $this->assertTrue(class_exists(TrainingDrill::class));
        $this->assertTrue(class_exists(TrainingSessionPlayer::class));
        $this->assertTrue(class_exists(Attendance::class));
        $this->assertTrue(class_exists(FitnessTest::class));
        $this->assertTrue(class_exists(PlayerAssessment::class));
        $this->assertTrue(class_exists(TrainingObjective::class));
        $this->assertTrue(class_exists(DevelopmentPlan::class));
    }

    public function test_p10_tables_exist_after_migration(): void
    {
        foreach ([
            'training_sessions',
            'training_drills',
            'training_session_drill',
            'training_session_players',
            'attendance',
            'fitness_tests',
            'player_assessments',
            'training_objectives',
            'development_plans',
        ] as $table) {
            $this->assertTrue(
                Schema::hasTable($table),
                "Missing {$table}"
            );
        }
    }

    public function test_p10_routes_are_registered(): void
    {
        $uris = collect(Route::getRoutes())
            ->map(fn ($route) => $route->uri())
            ->values();

        foreach ([
            'api/v1/organizations/{organization}/training/options',
            'api/v1/organizations/{organization}/training/sessions',
            'api/v1/organizations/{organization}/training/sessions/{trainingSession}',
            'api/v1/organizations/{organization}/training/sessions/{trainingSession}/players',
            'api/v1/organizations/{organization}/training/sessions/{trainingSession}/attendance',
            'api/v1/organizations/{organization}/training/drills',
            'api/v1/organizations/{organization}/training/players/{player}/fitness-tests',
            'api/v1/organizations/{organization}/training/fitness-tests',
            'api/v1/organizations/{organization}/training/players/{player}/assessments',
            'api/v1/organizations/{organization}/training/assessments',
            'api/v1/organizations/{organization}/training/players/{player}/objectives',
            'api/v1/organizations/{organization}/training/objectives',
            'api/v1/organizations/{organization}/training/players/{player}/development-plans',
            'api/v1/organizations/{organization}/training/development-plans',
        ] as $uri) {
            $this->assertTrue(
                $uris->contains($uri),
                "Missing route: {$uri}"
            );
        }
    }

    public function test_p10_core_columns_exist(): void
    {
        $this->assertTrue(Schema::hasColumns('training_sessions', [
            'organization_id',
            'team_id',
            'coach_id',
            'session_date',
            'duration_minutes',
            'session_type',
            'status',
        ]));

        $this->assertTrue(Schema::hasColumns('fitness_tests', [
            'player_id',
            'test_type',
            'tested_at',
            'value',
            'unit',
            'measurements',
        ]));

        $this->assertTrue(Schema::hasColumns('training_objectives', [
            'player_id',
            'weakness',
            'statistic_scope',
            'metric_key',
            'observed_value',
            'target_value',
            'source_context',
        ]));

        $this->assertTrue(Schema::hasColumns('development_plans', [
            'player_id',
            'weakness',
            'objectives',
            'objective_ids',
            'drill_ids',
            'start_date',
            'target_date',
            'status',
        ]));
    }
}
