<?php

namespace Tests\Unit\Realtime;

use App\Events\Realtime\DeliveryRecorded;
use App\Events\Realtime\InningsCompleted;
use App\Events\Realtime\MatchCompleted;
use App\Events\Realtime\WicketRecorded;
use App\Models\CricketMatch;
use App\Services\Realtime\LiveMatchStateService;
use Mockery;
use Tests\TestCase;

class MatchBroadcastEventTest extends TestCase
{
    public function test_realtime_events_use_expected_names_and_payload_shape(): void
    {
        $match = new CricketMatch();
        $match->id = 77;

        $snapshot = [
            'match_id' => 77,
            'status' => 'In Progress',
            'score' => [
                'runs' => 142,
                'wickets' => 3,
                'overs' => '17.4',
            ],
        ];

        $service = Mockery::mock(
            LiveMatchStateService::class
        );

        $service
            ->shouldReceive('snapshot')
            ->times(4)
            ->andReturn($snapshot);

        $this->app->instance(
            LiveMatchStateService::class,
            $service
        );

        $events = [
            new DeliveryRecorded($match),
            new WicketRecorded($match),
            new InningsCompleted($match),
            new MatchCompleted($match),
        ];

        $this->assertSame(
            'delivery.recorded',
            $events[0]->broadcastAs()
        );

        $this->assertSame(
            'wicket.recorded',
            $events[1]->broadcastAs()
        );

        $this->assertSame(
            'innings.completed',
            $events[2]->broadcastAs()
        );

        $this->assertSame(
            'match.completed',
            $events[3]->broadcastAs()
        );

        foreach ($events as $event) {
            $payload = $event->broadcastWith();

            $this->assertNotEmpty(
                $payload['event_id']
            );

            $this->assertSame(
                77,
                $payload['match_id']
            );

            $this->assertSame(
                $snapshot,
                $payload['snapshot']
            );

            $this->assertSame(
                'private-match.77',
                $event
                    ->broadcastOn()[0]
                    ->name
            );
        }
    }
}
