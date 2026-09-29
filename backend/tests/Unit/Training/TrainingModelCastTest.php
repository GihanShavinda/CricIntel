<?php

namespace Tests\Unit\Training;

use App\Models\DevelopmentPlan;
use App\Models\FitnessTest;
use App\Models\TrainingObjective;
use PHPUnit\Framework\TestCase;

class TrainingModelCastTest extends TestCase
{
    public function test_fitness_measurements_are_cast_to_array(): void
    {
        $model = new FitnessTest();

        $this->assertSame(
            'array',
            $model->getCasts()['measurements']
        );
    }

    public function test_objective_source_context_is_cast_to_array(): void
    {
        $model = new TrainingObjective();

        $this->assertSame(
            'array',
            $model->getCasts()['source_context']
        );
    }

    public function test_development_plan_links_are_cast_to_arrays(): void
    {
        $model = new DevelopmentPlan();
        $casts = $model->getCasts();

        $this->assertSame('array', $casts['objectives']);
        $this->assertSame('array', $casts['objective_ids']);
        $this->assertSame('array', $casts['drill_ids']);
    }
}
