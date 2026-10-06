<?php

namespace App\Events\Notifications;

class PlayerRemoved extends CricIntelNotificationEvent
{
    public function type(): string
    {
        return 'player_removed';
    }

    public function title(): string
    {
        return $this->data['title'] ?? 'Player removed';
    }

    public function message(): string
    {
        return $this->data['message'] ?? 'A player has been removed from selection.';
    }

    public function emailRecommended(): bool
    {
        return true;
    }
}
