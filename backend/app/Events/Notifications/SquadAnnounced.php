<?php

namespace App\Events\Notifications;

class SquadAnnounced extends CricIntelNotificationEvent
{
    public function type(): string
    {
        return 'squad_announced';
    }

    public function title(): string
    {
        return $this->data['title'] ?? 'Squad announced';
    }

    public function message(): string
    {
        return $this->data['message'] ?? 'The squad has been announced.';
    }

    public function emailRecommended(): bool
    {
        return true;
    }
}
