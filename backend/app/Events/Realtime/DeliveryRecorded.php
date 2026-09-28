<?php

namespace App\Events\Realtime;

class DeliveryRecorded extends MatchBroadcastEvent
{
    public function broadcastAs(): string
    {
        return 'delivery.recorded';
    }
}
