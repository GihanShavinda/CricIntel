<?php

namespace Tests\Unit\Strategy;

use App\Services\Strategy\StrategySectionTemplateService;
use PHPUnit\Framework\TestCase;

class StrategySectionTemplateServiceTest extends TestCase
{
    public function test_required_tactical_sections_are_created_in_order(): void
    {
        $service = new StrategySectionTemplateService();

        $keys = collect($service->templates())
            ->pluck('key')
            ->all();

        $this->assertSame([
            'pitch_notes',
            'weather_notes',
            'available_players',
            'recent_form',
            'playing_xi',
            'batting_plan',
            'bowling_plan',
            'matchups',
            'opponent_threats',
            'powerplay_strategy',
            'middle_over_strategy',
            'death_over_strategy',
        ], $keys);
    }
}
