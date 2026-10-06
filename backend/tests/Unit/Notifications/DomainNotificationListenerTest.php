<?php

namespace Tests\Unit\Notifications;

use App\Events\Notifications\FixtureChanged;
use App\Listeners\Notifications\SendCricIntelDomainNotification;
use App\Models\User;
use App\Notifications\CricIntelDomainNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DomainNotificationListenerTest extends TestCase
{
    use RefreshDatabase;

    public function test_listener_sends_domain_notification_to_recipient(): void
    {
        Notification::fake();

        $recipient = User::factory()->create();

        $event = new FixtureChanged(
            organizationId: 1,
            actorId: null,
            recipientUserIds: [
                $recipient->id,
            ],
            data: [
                'title' => 'Fixture changed',
                'message' => 'Fixture time changed.',
                'url' => '/organizations/1/fixtures',
            ]
        );

        app(
            SendCricIntelDomainNotification::class
        )->handle($event);

        Notification::assertSentTo(
            $recipient,
            CricIntelDomainNotification::class,
            function (
                CricIntelDomainNotification $notification
            ) {
                $payload =
                    $notification->toArray(
                        new \stdClass()
                    );

                return
                    $payload['type'] ===
                        'fixture_changed' &&
                    $payload['title'] ===
                        'Fixture changed';
            }
        );
    }
}
