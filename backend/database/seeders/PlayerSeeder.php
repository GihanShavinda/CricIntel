<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlayerSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::with('clubs.teams')->first();

        if (! $organization) {
            return;
        }

        $team = $organization->clubs
            ->flatMap(fn ($club) => $club->teams)
            ->first();

        $players = [
            [
                'first_name' => 'Nimal',
                'last_name' => 'Perera',
                'display_name' => 'Nimal Perera',
                'date_of_birth' => '1998-03-14',
                'nationality' => 'Sri Lankan',
                'primary_role' => 'Batter',
                'batting_style' => 'Right-handed',
                'bowling_style' => null,
                'fitness_status' => 'Fit',
                'status' => 'Active',
                'positions' => ['Opening Batter', 'Top Order'],
                'jersey' => 18,
            ],
            [
                'first_name' => 'Kasun',
                'last_name' => 'Silva',
                'display_name' => 'Kasun Silva',
                'date_of_birth' => '1997-08-22',
                'nationality' => 'Sri Lankan',
                'primary_role' => 'All-rounder',
                'batting_style' => 'Left-handed',
                'bowling_style' => 'Right-arm medium',
                'fitness_status' => 'Fit',
                'status' => 'Active',
                'positions' => ['Middle Order', 'Seam Bowling'],
                'jersey' => 27,
            ],
            [
                'first_name' => 'Dinesh',
                'last_name' => 'Fernando',
                'display_name' => 'Dinesh Fernando',
                'date_of_birth' => '1999-11-02',
                'nationality' => 'Sri Lankan',
                'primary_role' => 'Bowler',
                'batting_style' => 'Right-handed',
                'bowling_style' => 'Right-arm fast',
                'fitness_status' => 'Under Observation',
                'status' => 'Active',
                'positions' => ['Fast Bowler'],
                'jersey' => 91,
            ],
            [
                'first_name' => 'Chamari',
                'last_name' => 'Wijesinghe',
                'display_name' => 'Chamari Wijesinghe',
                'date_of_birth' => '2000-05-17',
                'nationality' => 'Sri Lankan',
                'primary_role' => 'Wicketkeeper-Batter',
                'batting_style' => 'Right-handed',
                'bowling_style' => null,
                'fitness_status' => 'Fit',
                'status' => 'Active',
                'positions' => ['Wicketkeeper', 'Middle Order'],
                'jersey' => 7,
            ],
            [
                'first_name' => 'Ravindu',
                'last_name' => 'Jayasinghe',
                'display_name' => 'Ravindu Jayasinghe',
                'date_of_birth' => '2001-01-30',
                'nationality' => 'Sri Lankan',
                'primary_role' => 'Bowler',
                'batting_style' => 'Left-handed',
                'bowling_style' => 'Left-arm orthodox',
                'fitness_status' => 'Rehabilitation',
                'status' => 'Injured',
                'positions' => ['Spin Bowler'],
                'jersey' => 44,
            ],
        ];

        DB::transaction(function () use ($organization, $team, $players) {
            foreach ($players as $index => $data) {
                $positions = $data['positions'];
                $jersey = $data['jersey'];
                unset($data['positions'], $data['jersey']);

                $player = $organization->players()->updateOrCreate(
                    ['display_name' => $data['display_name']],
                    $data
                );

                $player->positions()->delete();
                foreach ($positions as $priority => $position) {
                    $player->positions()->create([
                        'position' => $position,
                        'priority' => $priority + 1,
                    ]);
                }

                if ($team) {
                    $player->teams()->syncWithoutDetaching([
                        $team->id => [
                            'jersey_number' => $jersey,
                            'joined_at' => '2026-01-01',
                            'left_at' => null,
                            'is_current' => true,
                        ],
                    ]);
                }

                $player->availability()->updateOrCreate(
                    [
                        'available_from' => '2026-09-01',
                        'status' => $data['status'] === 'Injured'
                            ? 'Unavailable'
                            : 'Available',
                    ],
                    [
                        'available_to' => null,
                        'reason' => $data['status'] === 'Injured'
                            ? 'Medical rehabilitation'
                            : 'Available for selection',
                    ]
                );
            }
        });
    }
}
