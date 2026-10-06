<?php

namespace App\Events\Notifications;

class AnalystMentioned extends CricIntelNotificationEvent
{
    public function type(): string
    {
        return 'analyst_mentioned';
    }

    public function title(): string
    {
        return $this->data['title'] ?? 'You were mentioned';
    }

    public function message(): string
    {
        return $this->data['message'] ?? 'You were mentioned in a tactical discussion.';
    }
}
