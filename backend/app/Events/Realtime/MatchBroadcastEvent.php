<?php

namespace App\Events\Realtime;

use App\Models\CricketMatch;
use App\Services\Realtime\LiveMatchStateService;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

abstract class MatchBroadcastEvent implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public string $eventId;
    public array $snapshot;

    public function __construct(
        public CricketMatch $match
    ) {
        $this->eventId = (string) Str::uuid();
        $this->snapshot = app(LiveMatchStateService::class)
            ->snapshot($match);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(
                'match.'.$this->match->id
            ),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'event_id' => $this->eventId,
            'event_name' => $this->broadcastAs(),
            'match_id' => (int) $this->match->id,
            'emitted_at' => now()->toIso8601String(),
            'snapshot' => $this->snapshot,
        ];
    }

    abstract public function broadcastAs(): string;
}
