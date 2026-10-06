<?php

namespace App\Events\Notifications;

class CoachCommentAdded extends CricIntelNotificationEvent
{
    public function type(): string
    {
        return 'coach_comment_added';
    }

    public function title(): string
    {
        return $this->data['title'] ?? 'Coach comment added';
    }

    public function message(): string
    {
        return $this->data['message'] ?? 'A coach added a tactical comment.';
    }
}
