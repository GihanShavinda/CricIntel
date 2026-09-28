<?php

namespace App\Events\Realtime;

class WicketRecorded extends MatchBroadcastEvent
{
    public function broadcastAs(): string
    {
        return 'wicket.recorded';
    }
}
