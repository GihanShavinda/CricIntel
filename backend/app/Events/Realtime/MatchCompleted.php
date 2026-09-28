<?php

namespace App\Events\Realtime;

class MatchCompleted extends MatchBroadcastEvent
{
    public function broadcastAs(): string
    {
        return 'match.completed';
    }
}
