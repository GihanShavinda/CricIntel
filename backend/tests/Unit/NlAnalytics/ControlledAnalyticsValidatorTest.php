<?php

namespace Tests\Unit\NlAnalytics;

use App\Models\Organization;
use App\Services\NlAnalytics\ControlledAnalyticsSchema;
use App\Services\NlAnalytics\ControlledAnalyticsValidator;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ControlledAnalyticsValidatorTest extends TestCase
{
    public function test_rejects_invalid_metric_for_intent_before_database_resolution(): void
    {
        $validator = new ControlledAnalyticsValidator(
            new ControlledAnalyticsSchema()
        );

        $organization = new Organization();
        $organization->id = 1;

        $this->expectException(
            ValidationException::class
        );

        $validator->validate(
            $organization,
            [
                'intent' =>
                    'rank_bowlers_phase_vs_hand',
                'metric' => 'password',
                'entity' => 'bowler',
                'team_id' => 1,
                'phase' => 'death',
                'batting_hand' => 'left',
                'sort_direction' => 'asc',
                'limit' => 10,
            ]
        );
    }

    public function test_rejects_invalid_entity_for_intent_before_database_resolution(): void
    {
        $validator = new ControlledAnalyticsValidator(
            new ControlledAnalyticsSchema()
        );

        $organization = new Organization();
        $organization->id = 1;

        $this->expectException(
            ValidationException::class
        );

        $validator->validate(
            $organization,
            [
                'intent' =>
                    'team_run_rate_trend',
                'metric' => 'run_rate',
                'entity' => 'user',
                'team_id' => 1,
                'phase' => 'middle',
                'batting_hand' => 'all',
                'sort_direction' => 'desc',
                'limit' => 10,
            ]
        );
    }
}
