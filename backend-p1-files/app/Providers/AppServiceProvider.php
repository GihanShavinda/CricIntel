<?php

namespace App\Providers;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::before(function (User $user, string $ability) {
            return $user->hasRole(RoleName::Administrator->value)
                ? true
                : null;
        });

        Gate::define(
            'access-administration',
            fn (User $user): bool => false
        );

        Gate::define(
            'access-coaching',
            fn (User $user): bool =>
                $user->hasAnyRole([
                    RoleName::Coach->value,
                    RoleName::TeamManager->value,
                ])
        );

        Gate::define(
            'access-analysis',
            fn (User $user): bool =>
                $user->hasAnyRole([
                    RoleName::Analyst->value,
                    RoleName::Coach->value,
                    RoleName::Selector->value,
                ])
        );

        Gate::define(
            'access-selection',
            fn (User $user): bool =>
                $user->hasAnyRole([
                    RoleName::Selector->value,
                    RoleName::Coach->value,
                ])
        );
    }
}
