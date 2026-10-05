<?php

namespace App\Services\Strategy;

class StrategySectionTemplateService
{
    public function templates(): array
    {
        return [
            ['key' => 'pitch_notes', 'title' => 'Pitch Notes', 'sort_order' => 10],
            ['key' => 'weather_notes', 'title' => 'Weather Notes', 'sort_order' => 20],
            ['key' => 'available_players', 'title' => 'Available Players', 'sort_order' => 30],
            ['key' => 'recent_form', 'title' => 'Recent Form', 'sort_order' => 40],
            ['key' => 'playing_xi', 'title' => 'Playing XI', 'sort_order' => 50],
            ['key' => 'batting_plan', 'title' => 'Batting Plan', 'sort_order' => 60],
            ['key' => 'bowling_plan', 'title' => 'Bowling Plan', 'sort_order' => 70],
            ['key' => 'matchups', 'title' => 'Matchups', 'sort_order' => 80],
            ['key' => 'opponent_threats', 'title' => 'Opponent Threats', 'sort_order' => 90],
            ['key' => 'powerplay_strategy', 'title' => 'Powerplay Strategy', 'sort_order' => 100],
            ['key' => 'middle_over_strategy', 'title' => 'Middle-over Strategy', 'sort_order' => 110],
            ['key' => 'death_over_strategy', 'title' => 'Death-over Strategy', 'sort_order' => 120],
        ];
    }
}
