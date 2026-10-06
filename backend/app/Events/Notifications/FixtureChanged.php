<?php

namespace App\Events\Notifications;

class FixtureChanged extends CricIntelNotificationEvent
{
    public function type(): string
    {
        return 'fixture_changed';
    }

    public function title(): string
    {
        return $this->data['title'] ?? 'Fixture changed';
    }

    public function message(): string
    {
        return $this->data['message'] ?? 'A fixture has been changed.';
    }

    public function emailRecommended(): bool
    {
        return true;
    }
}
