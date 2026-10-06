<?php

namespace Tests\Unit\Notifications;

use App\Notifications\CricIntelDomainNotification;
use Tests\TestCase;

class CricIntelDomainNotificationTest extends TestCase
{
    public function test_notification_preserves_grounded_domain_payload(): void
    {
        $notification =
            new CricIntelDomainNotification(
                [
                    'type' => 'match_starting',
                    'title' => 'Match starting',
                    'message' => 'Team A vs Team B is starting.',
                    'url' => '/organizations/1/matches/20',
                    'organization_id' => 1,
                    'actor_id' => 5,
                    'data' => [
                        'match_id' => 20,
                    ],
                ],
                true
            );

        $payload = $notification->toArray(
            new \stdClass()
        );

        $this->assertSame(
            'match_starting',
            $payload['type']
        );

        $this->assertSame(
            20,
            $payload['data']['match_id']
        );
    }
}
