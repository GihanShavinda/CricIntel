<?php

namespace Tests\Unit\Scouting;

use App\Services\Scouting\ScoutingRatingService;
use PHPUnit\Framework\TestCase;

class ScoutingRatingServiceTest extends TestCase
{
    public function test_overall_rating_is_deterministic_average(): void
    {
        $service = new ScoutingRatingService();

        $overall = $service->overall([
            'technical_rating' => 8,
            'tactical_rating' => 7,
            'physical_rating' => 6,
            'fielding_rating' => 9,
            'mental_decision_rating' => 10,
        ]);

        $this->assertSame(8.0, $overall);
    }

    public function test_overall_rating_keeps_two_decimal_precision(): void
    {
        $service = new ScoutingRatingService();

        $overall = $service->overall([
            'technical_rating' => 8,
            'tactical_rating' => 8,
            'physical_rating' => 7,
            'fielding_rating' => 7,
            'mental_decision_rating' => 9,
        ]);

        $this->assertSame(7.8, $overall);
    }
}
