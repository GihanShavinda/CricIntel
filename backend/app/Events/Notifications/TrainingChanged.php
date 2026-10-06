<?php

namespace App\Events\Notifications;

class TrainingChanged extends CricIntelNotificationEvent
{
    public function type(): string
    {
        return 'training_changed';
    }

    public function title(): string
    {
        return $this->data['title'] ?? 'Training changed';
    }

    public function message(): string
    {
        return $this->data['message'] ?? 'A training session has changed.';
    }

    public function emailRecommended(): bool
    {
        return true;
    }
}
