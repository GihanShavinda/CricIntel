<?php

namespace App\Events\Notifications;

class TrainingAssigned extends CricIntelNotificationEvent
{
    public function type(): string
    {
        return 'training_assigned';
    }

    public function title(): string
    {
        return $this->data['title'] ?? 'Training assigned';
    }

    public function message(): string
    {
        return $this->data['message'] ?? 'A training session has been assigned.';
    }

    public function emailRecommended(): bool
    {
        return true;
    }
}
