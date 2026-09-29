<?php

namespace App\Services\Scouting;

use App\Models\Player;
use App\Models\ScoutingProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ScoutingConversionService
{
    public function convert(
        ScoutingProfile $profile,
        array $data
    ): Player {
        if ($profile->converted_player_id) {
            throw ValidationException::withMessages([
                'profile' => 'This scouting profile has already been converted.',
            ]);
        }

        return DB::transaction(function () use ($profile, $data) {
            $player = Player::query()->create([
                'organization_id' => $profile->organization_id,
                'first_name' => $profile->first_name,
                'last_name' => $profile->last_name,
                'display_name' => $profile->display_name,
                'date_of_birth' => $profile->date_of_birth,
                'nationality' => $profile->nationality,
                'primary_role' => $data['primary_role'],
                'batting_style' => $data['batting_style'] ?? $profile->batting_style,
                'bowling_style' => $data['bowling_style'] ?? $profile->bowling_style,
                'fitness_status' => $data['fitness_status'] ?? 'Unknown',
                'status' => $data['status'] ?? 'active',
                'notes' => $data['notes'] ?? $profile->summary,
            ]);

            $profile->update([
                'converted_player_id' => $player->id,
                'status' => 'Converted',
            ]);

            return $player;
        });
    }
}
