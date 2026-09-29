<?php

namespace App\Services\Scouting;

class ScoutingRatingService
{
    public function overall(array $ratings): float
    {
        $values = [
            (int) $ratings['technical_rating'],
            (int) $ratings['tactical_rating'],
            (int) $ratings['physical_rating'],
            (int) $ratings['fielding_rating'],
            (int) $ratings['mental_decision_rating'],
        ];

        return round(array_sum($values) / count($values), 2);
    }
}
