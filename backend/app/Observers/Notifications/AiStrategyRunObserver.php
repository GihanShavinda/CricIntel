<?php

namespace App\Observers\Notifications;

use App\Events\Notifications\TacticalReportReady;
use App\Models\AiStrategyRun;
use App\Services\Notifications\NotificationAudienceService;

class AiStrategyRunObserver
{
    public function __construct(
        private readonly NotificationAudienceService $audience
    ) {}

    public function updated(
        AiStrategyRun $run
    ): void {
        if (
            ! $run->wasChanged('validation_status') ||
            $run->validation_status !== 'accepted'
        ) {
            return;
        }

        TacticalReportReady::dispatch(
            (int) $run->organization_id,
            $run->user_id,
            $this->audience
                ->tacticalReportRecipients($run),
            [
                'ai_strategy_run_id' => $run->id,
                'match_id' => $run->match_id,
                'title' => 'Tactical report ready',
                'message' =>
                    'A grounded CricIntel tactical assistant report has passed validation and is ready.',
                'url' =>
                    "/organizations/{$run->organization_id}/strategy-assistant",
            ]
        );
    }
}
