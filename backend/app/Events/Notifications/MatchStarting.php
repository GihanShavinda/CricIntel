<?php

namespace App\Events\Notifications;

class MatchStarting extends CricIntelNotificationEvent
{
    public function type(): string
    {
        return 'match_starting';
    }

    public function title(): string
    {
        return $this->data['title'] ?? 'Match starting';
    }

    public function message(): string
    {
        return $this->data['message'] ?? 'A match is starting.';
    }

    public function emailRecommended(): bool
    {
        return true;
    }
}
