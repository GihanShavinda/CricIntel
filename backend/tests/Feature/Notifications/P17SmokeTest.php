<?php

namespace Tests\Feature\Notifications;

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
use App\Http\Controllers\Api\V1\NotificationController;
use App\Listeners\Notifications\SendCricIntelDomainNotification;
use App\Models\NotificationPreference;
use App\Notifications\CricIntelDomainNotification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class P17SmokeTest extends TestCase
{
    public function test_p17_notification_classes_exist(): void
    {
        foreach ([
            NotificationController::class,
            NotificationPreference::class,
            CricIntelDomainNotification::class,
            SendCricIntelDomainNotification::class,

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
        ] as $class) {
            $this->assertTrue(
                class_exists($class),
                "Missing P17 class: {$class}"
            );
        }
    }

    public function test_p17_notification_tables_exist(): void
    {
        $this->assertTrue(
            Schema::hasTable('notifications')
        );

        $this->assertTrue(
            Schema::hasTable('notification_preferences')
        );

        $this->assertTrue(
            Schema::hasColumns(
                'notification_preferences',
                [
                    'user_id',
                    'event_type',
                    'database_enabled',
                    'email_enabled',
                    'realtime_enabled',
                ]
            )
        );
    }

    public function test_p17_notification_routes_are_registered(): void
    {
        $uris = collect(
            Route::getRoutes()
        )
            ->map(
                fn ($route) =>
                    $route->uri()
            )
            ->values();

        foreach (
            [
                'api/v1/notifications',
                'api/v1/notifications/unread-count',
                'api/v1/notifications/{notification}/read',
                'api/v1/notifications/{notification}/unread',
                'api/v1/notifications/read-all',
                'api/v1/notifications/{notification}',
                'api/v1/notification-preferences',
            ] as $uri
        ) {
            $this->assertTrue(
                $uris->contains($uri),
                "Missing P17 route: {$uri}"
            );
        }
    }

    public function test_p17_retry_strategy_is_configured(): void
    {
        $this->assertSame(
            4,
            config('cricintel_notifications.tries')
        );

        $this->assertSame(
            [30, 120, 300],
            config('cricintel_notifications.backoff')
        );

        $this->assertSame(
            'notifications',
            config('cricintel_notifications.queue')
        );
    }
}
