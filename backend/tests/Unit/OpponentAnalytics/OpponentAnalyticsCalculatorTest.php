<?php

namespace Tests\Unit\OpponentAnalytics;

use App\Services\OpponentAnalytics\OpponentAnalyticsCalculator;
use PHPUnit\Framework\TestCase;

class OpponentAnalyticsCalculatorTest extends TestCase
{
    private OpponentAnalyticsCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new OpponentAnalyticsCalculator();
    }

    public function test_known_batting_strike_rate(): void
    {
        $this->assertSame(
            130.0,
            $this->calculator->strikeRate(78, 60)
        );
    }

    public function test_known_bowling_economy(): void
    {
        $this->assertSame(
            7.5,
            $this->calculator->economy(30, 24)
        );
    }

    public function test_known_wicket_rate_per_100_balls(): void
    {
        $this->assertSame(
            10.0,
            $this->calculator->wicketRate(6, 60)
        );
    }

    public function test_t20_phase_boundaries_are_deterministic(): void
    {
        $this->assertSame('Powerplay', $this->calculator->phaseForOver(1, 20));
        $this->assertSame('Powerplay', $this->calculator->phaseForOver(6, 20));
        $this->assertSame('Middle', $this->calculator->phaseForOver(7, 20));
        $this->assertSame('Middle', $this->calculator->phaseForOver(15, 20));
        $this->assertSame('Death', $this->calculator->phaseForOver(16, 20));
        $this->assertSame('Death', $this->calculator->phaseForOver(20, 20));
    }

    public function test_odi_phase_boundaries_are_deterministic(): void
    {
        $this->assertSame('Powerplay', $this->calculator->phaseForOver(10, 50));
        $this->assertSame('Middle', $this->calculator->phaseForOver(11, 50));
        $this->assertSame('Middle', $this->calculator->phaseForOver(40, 50));
        $this->assertSame('Death', $this->calculator->phaseForOver(41, 50));
    }

    public function test_bowling_style_classification_supports_required_matchups(): void
    {
        $this->assertSame(
            'left_arm_pace',
            $this->calculator->bowlingCategory('Left-arm fast-medium')
        );

        $this->assertSame(
            'off_spin',
            $this->calculator->bowlingCategory('Right-arm off spin')
        );

        $this->assertSame(
            'left_arm_spin',
            $this->calculator->bowlingCategory('Slow left arm orthodox')
        );

        $this->assertSame(
            'pace',
            $this->calculator->bowlingCategory('Right-arm fast')
        );
    }

    public function test_sample_size_limitations_are_explicit(): void
    {
        $this->assertSame(
            'very_small',
            $this->calculator->sampleSize(8)['level']
        );

        $this->assertSame(
            'small',
            $this->calculator->sampleSize(20)['level']
        );

        $this->assertSame(
            'adequate',
            $this->calculator->sampleSize(30)['level']
        );
    }

    public function test_run_out_is_not_credited_as_bowler_dismissal(): void
    {
        $delivery = (object) [
            'wicket' => true,
            'wicket_type' => 'run_out',
        ];

        $this->assertFalse(
            $this->calculator->normalizedDismissalWicket($delivery)
        );
    }

    public function test_bowled_is_credited_as_bowler_dismissal(): void
    {
        $delivery = (object) [
            'wicket' => true,
            'wicket_type' => 'bowled',
        ];

        $this->assertTrue(
            $this->calculator->normalizedDismissalWicket($delivery)
        );
    }
}
