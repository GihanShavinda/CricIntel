<?php

namespace App\Providers;

use App\Enums\RoleName;

use App\Events\Notifications\AnalystMentioned;
use App\Events\Notifications\CoachCommentAdded;
use App\Events\Notifications\FixtureChanged;
use App\Events\Notifications\MatchStarting;
use App\Events\Notifications\PlayerAvailabilityChanged;
use App\Events\Notifications\PlayerMarkedInjured;
use App\Events\Notifications\PlayerRemoved;
use App\Events\Notifications\PlayerSelected;
use App\Events\Notifications\SquadAnnounced;
use App\Events\Notifications\TacticalReportReady;
use App\Events\Notifications\TrainingAssigned;
use App\Events\Notifications\TrainingChanged;

use App\Listeners\Notifications\SendCricIntelDomainNotification;

use App\Models\AiStrategyRun;
use App\Models\CricketMatch;
use App\Models\Delivery;
use App\Models\Fixture;
use App\Models\Innings;
use App\Models\MatchSquadPlayer;
use App\Models\Mention;
use App\Models\Player;
use App\Models\PlayerAvailability;
use App\Models\Squad;
use App\Models\StrategyComment;
use App\Models\TrainingSession;
use App\Models\TrainingSessionPlayer;
use App\Models\User;
use App\Models\Wicket;

use App\Observers\Notifications\AiStrategyRunObserver;
use App\Observers\Notifications\CricketMatchObserver;
use App\Observers\Notifications\FixtureObserver;
use App\Observers\Notifications\MatchSquadPlayerObserver;
use App\Observers\Notifications\MentionObserver;
use App\Observers\Notifications\PlayerAvailabilityObserver;
use App\Observers\Notifications\PlayerObserver;
use App\Observers\Notifications\SquadObserver;
use App\Observers\Notifications\StrategyCommentObserver;
use App\Observers\Notifications\TrainingSessionObserver;
use App\Observers\Notifications\TrainingSessionPlayerObserver;

use App\Observers\StatisticsCacheInvalidationObserver;

use Illuminate\Support\Facades\Event;
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


        /*
        |--------------------------------------------------------------------------
        | P17 Notification Domain Event Listeners
        |--------------------------------------------------------------------------
        |
        | CricIntel domain events are routed to one queued notification
        | listener.
        |
        | The listener resolves recipients and sends a preference-aware
        | notification through:
        |
        | - database
        | - email
        | - Reverb broadcast
        |
        */

        $notificationEvents = [
            SquadAnnounced::class,
            PlayerSelected::class,
            PlayerRemoved::class,
            TrainingAssigned::class,
            TrainingChanged::class,
            FixtureChanged::class,
            MatchStarting::class,
            TacticalReportReady::class,
            PlayerAvailabilityChanged::class,
            PlayerMarkedInjured::class,
            AnalystMentioned::class,
            CoachCommentAdded::class,
        ];

        foreach (
            $notificationEvents as $eventClass
        ) {
            Event::listen(
                $eventClass,
                SendCricIntelDomainNotification::class
            );
        }


        /*
        |--------------------------------------------------------------------------
        | P17 Notification Model Observers
        |--------------------------------------------------------------------------
        |
        | These observers convert existing CricIntel domain changes into
        | explicit notification events.
        |
        */


        /*
        |--------------------------------------------------------------------------
        | Squad Notifications
        |--------------------------------------------------------------------------
        |
        | Draft / selection workflow changes can generate:
        |
        | SquadAnnounced
        |
        */

        Squad::observe(
            SquadObserver::class
        );


        /*
        |--------------------------------------------------------------------------
        | Match Squad Selection Notifications
        |--------------------------------------------------------------------------
        |
        | Creates:
        |
        | PlayerSelected
        | PlayerRemoved
        |
        */

        MatchSquadPlayer::observe(
            MatchSquadPlayerObserver::class
        );


        /*
        |--------------------------------------------------------------------------
        | Training Notifications
        |--------------------------------------------------------------------------
        |
        | Creates:
        |
        | TrainingAssigned
        | TrainingChanged
        |
        */

        TrainingSession::observe(
            TrainingSessionObserver::class
        );

        TrainingSessionPlayer::observe(
            TrainingSessionPlayerObserver::class
        );


        /*
        |--------------------------------------------------------------------------
        | Fixture Notifications
        |--------------------------------------------------------------------------
        |
        | Creates:
        |
        | FixtureChanged
        |
        */

        Fixture::observe(
            FixtureObserver::class
        );


        /*
        |--------------------------------------------------------------------------
        | Match Notifications
        |--------------------------------------------------------------------------
        |
        | Creates:
        |
        | MatchStarting
        |
        | IMPORTANT:
        | CricketMatch already uses StatisticsCacheInvalidationObserver above.
        | Laravel supports multiple observers on the same model.
        |
        */

        CricketMatch::observe(
            CricketMatchObserver::class
        );


        /*
        |--------------------------------------------------------------------------
        | AI Tactical Report Notifications
        |--------------------------------------------------------------------------
        |
        | When a P15 grounded strategy run is validated and accepted:
        |
        | TacticalReportReady
        |
        */

        AiStrategyRun::observe(
            AiStrategyRunObserver::class
        );


        /*
        |--------------------------------------------------------------------------
        | Player Availability Notifications
        |--------------------------------------------------------------------------
        |
        | Creates:
        |
        | PlayerAvailabilityChanged
        |
        */

        PlayerAvailability::observe(
            PlayerAvailabilityObserver::class
        );


        /*
        |--------------------------------------------------------------------------
        | Player Fitness / Injury Notifications
        |--------------------------------------------------------------------------
        |
        | Creates:
        |
        | PlayerMarkedInjured
        |
        */

        Player::observe(
            PlayerObserver::class
        );


        /*
        |--------------------------------------------------------------------------
        | Tactical Mention Notifications
        |--------------------------------------------------------------------------
        |
        | Creates:
        |
        | AnalystMentioned
        |
        */

        Mention::observe(
            MentionObserver::class
        );


        /*
        |--------------------------------------------------------------------------
        | Tactical Comment Notifications
        |--------------------------------------------------------------------------
        |
        | Creates:
        |
        | CoachCommentAdded
        |
        */

        StrategyComment::observe(
            StrategyCommentObserver::class
        );
    }
}
