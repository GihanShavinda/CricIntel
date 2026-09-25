<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $descriptions = [
            RoleName::Administrator->value =>
                'Full platform administration access.',
            RoleName::Coach->value =>
                'Coaching and team-management capabilities.',
            RoleName::Analyst->value =>
                'Performance and match analysis capabilities.',
            RoleName::Selector->value =>
                'Player and squad selection capabilities.',
            RoleName::TeamManager->value =>
                'Operational team-management capabilities.',
            RoleName::Player->value =>
                'Player-level access.',
        ];

        foreach (RoleName::cases() as $role) {
            Role::updateOrCreate(
                ['name' => $role->value],
                ['description' => $descriptions[$role->value]]
            );
        }
    }
}
