<?php

namespace App\Events\Notifications;

class PlayerAvailabilityChanged extends CricIntelNotificationEvent
{
    public function type(): string
    {
        return 'player_availability_changed';
    }

    public function title(): string
    {
        return $this->data['title'] ?? 'Player availability changed';
    }

    public function message(): string
    {
        return $this->data['message'] ?? 'Player availability has changed.';
    }
}
