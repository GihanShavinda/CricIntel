<?php

namespace Tests\Unit\Analytics;

use App\Services\Analytics\AnalyticsQuery;
use Tests\TestCase;

class AnalyticsQueryTest extends TestCase
{
    public function test_phase_expression_is_deterministic(): void
    {
        $service = app(AnalyticsQuery::class);

        $sql = $service->phaseExpression();

        $this->assertStringContainsString(
            "o.over_number BETWEEN 1 AND 6",
            $sql
        );

        $this->assertStringContainsString(
            "o.over_number BETWEEN 7 AND 15",
            $sql
        );

        $this->assertStringContainsString(
            "ELSE 'death'",
            $sql
        );
    }

    public function test_bowling_type_expression_uses_stored_bowling_style(): void
    {
        $service = app(AnalyticsQuery::class);

        $sql = $service->bowlingTypeExpression('bp');

        $this->assertStringContainsString(
            'bp.bowling_style',
            $sql
        );

        $this->assertStringContainsString(
            "'spin'",
            $sql
        );

        $this->assertStringContainsString(
            "'pace'",
            $sql
        );
    }

    public function test_opponent_expression_uses_fixture_team_ids(): void
    {
        $service = app(AnalyticsQuery::class);

        $sql = $service->opponentExpression(8);

        $this->assertStringContainsString(
            'f.home_team_id = 8',
            $sql
        );

        $this->assertStringContainsString(
            'f.away_team_id',
            $sql
        );
    }
}
