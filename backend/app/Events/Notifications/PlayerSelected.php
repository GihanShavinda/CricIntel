<?php

namespace App\Events\Notifications;

class PlayerSelected extends CricIntelNotificationEvent
{
    public function type(): string
    {
        return 'player_selected';
    }

    public function title(): string
    {
        return $this->data['title'] ?? 'Player selected';
    }

    public function message(): string
    {
        return $this->data['message'] ?? 'A player has been selected.';
    }

    public function emailRecommended(): bool
    {
        return true;
    }
}
