<?php

namespace App\Events\Notifications;

class PlayerMarkedInjured extends CricIntelNotificationEvent
{
    public function type(): string
    {
        return 'player_marked_injured';
    }

    public function title(): string
    {
        return $this->data['title'] ?? 'Player marked injured';
    }

    public function message(): string
    {
        return $this->data['message'] ?? 'A player has been marked injured.';
    }

    public function emailRecommended(): bool
    {
        return true;
    }
}
