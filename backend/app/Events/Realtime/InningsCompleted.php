<?php

namespace App\Events\Realtime;

class InningsCompleted extends MatchBroadcastEvent
{
    public function broadcastAs(): string
    {
        return 'innings.completed';
    }
}
