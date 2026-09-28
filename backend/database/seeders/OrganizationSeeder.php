<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $adminRole = Role::where('name', 'Administrator')->first();

            $admin = $adminRole
                ? User::whereHas('roles', fn ($q) => $q->where('roles.id', $adminRole->id))->first()
                : User::first();

            if (! $admin) {
                return;
            }

            $organization = Organization::updateOrCreate(
                ['name' => 'CricIntel Demo Organization'],
                [
                    'short_name' => 'CDO',
                    'country' => 'Sri Lanka',
                    'timezone' => 'Asia/Colombo',
                    'description' => 'Sample organization created for CricIntel P2.',
                    'status' => 'active',
                    'created_by' => $admin->id,
                ]
            );

            $organization->members()->syncWithoutDetaching([
                $admin->id => ['title' => 'Administrator', 'status' => 'active'],
            ]);

            $club = $organization->clubs()->updateOrCreate(
                ['code' => 'CCC'],
                [
                    'name' => 'Colombo Cricket Club',
                    'location' => 'Colombo',
                    'founded_year' => 1863,
                    'description' => 'Sample CricIntel club.',
                ]
            );

            $team = $club->teams()->updateOrCreate(
                ['name' => 'CCC First XI'],
                [
                    'short_name' => 'CCC 1XI',
                    'gender' => 'male',
                    'category' => 'senior',
                    'age_group' => 'open',
                    'format_preferences' => ['T20','ODI'],
                    'home_ground' => 'CCC Ground',
                    'status' => 'active',
                ]
            );

            $season = $organization->seasons()->updateOrCreate(
                ['name' => '2026/27'],
                [
                    'start_date' => '2026-10-01',
                    'end_date' => '2027-04-30',
                    'status' => 'active',
                ]
            );

            $season->teams()->syncWithoutDetaching([$team->id]);
        });
    }
}
