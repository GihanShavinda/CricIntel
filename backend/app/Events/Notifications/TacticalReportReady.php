<?php

namespace App\Events\Notifications;

class TacticalReportReady extends CricIntelNotificationEvent
{
    public function type(): string
    {
        return 'tactical_report_ready';
    }

    public function title(): string
    {
        return $this->data['title'] ?? 'Tactical report ready';
    }

    public function message(): string
    {
        return $this->data['message'] ?? 'A tactical report is ready.';
    }

    public function emailRecommended(): bool
    {
        return true;
    }
}
