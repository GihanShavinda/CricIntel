<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            [
                'email' => 'admin@cricintel.local',
            ],
            [
                'name' => 'CricIntel Administrator',
                'password' => 'AdminPassword123',
                'status' => 'active',
            ]
        );

        $role = Role::where(
            'name',
            'Administrator'
        )->firstOrFail();

        $admin->roles()->syncWithoutDetaching([
            $role->id
        ]);
    }
}
