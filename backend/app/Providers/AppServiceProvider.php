<?php

namespace App\Providers;

use App\Enums\RoleName;

use App\Models\CricketMatch;
use App\Models\Delivery;
use App\Models\Innings;
use App\Models\User;
use App\Models\Wicket;

use App\Observers\StatisticsCacheInvalidationObserver;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap application services.
     */
    public function boot(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Global Administrator Override
        |--------------------------------------------------------------------------
        |
        | Administrators automatically pass all authorization gates.
        |
        */

        Gate::before(function (
            User $user,
            string $ability
        ) {
            return $user->hasRole(
                RoleName::Administrator->value
            )
                ? true
                : null;
        });


        /*
        |--------------------------------------------------------------------------
        | Administration Access
        |--------------------------------------------------------------------------
        |
        | This intentionally returns false because Administrator access is
        | handled by Gate::before() above.
        |
        */

        Gate::define(
            'access-administration',
            fn (User $user): bool =>
                false
        );


        /*
        |--------------------------------------------------------------------------
        | Coaching Access
        |--------------------------------------------------------------------------
        */

        Gate::define(
            'access-coaching',
            fn (User $user): bool =>
                $user->hasAnyRole([
                    RoleName::Coach->value,
                    RoleName::TeamManager->value,
                ])
        );


        /*
        |--------------------------------------------------------------------------
        | Analysis Access
        |--------------------------------------------------------------------------
        */

        Gate::define(
            'access-analysis',
            fn (User $user): bool =>
                $user->hasAnyRole([
                    RoleName::Analyst->value,
                    RoleName::Coach->value,
                    RoleName::Selector->value,
                ])
        );


        /*
        |--------------------------------------------------------------------------
        | Selection Access
        |--------------------------------------------------------------------------
        */

        Gate::define(
            'access-selection',
            fn (User $user): bool =>
                $user->hasAnyRole([
                    RoleName::Selector->value,
                    RoleName::Coach->value,
                ])
        );


        /*
        |--------------------------------------------------------------------------
        | P6 Statistics Cache Invalidation
        |--------------------------------------------------------------------------
        |
        | CricIntel statistics are calculated from stored:
        |
        | Match
        |  └── Innings
        |       └── Delivery
        |            └── Wicket
        |
        | Whenever one of these records changes, the statistics cache version
        | for the organization is incremented.
        |
        | This keeps player, team, match and phase statistics synchronized with
        | the latest scoring data.
        |
        | Works with the current:
        |
        | CACHE_STORE=file
        |
        | and later also:
        |
        | CACHE_STORE=redis
        |
        */

        CricketMatch::observe(
            StatisticsCacheInvalidationObserver::class
        );

        Innings::observe(
            StatisticsCacheInvalidationObserver::class
        );

        Delivery::observe(
            StatisticsCacheInvalidationObserver::class
        );

        Wicket::observe(
            StatisticsCacheInvalidationObserver::class
        );
    }
}
