<?php

namespace App\Observers\Notifications;

use App\Events\Notifications\TrainingChanged;
use App\Models\TrainingSession;
use App\Services\Notifications\NotificationAudienceService;

class TrainingSessionObserver
{
    public function __construct(
        private readonly NotificationAudienceService $audience
    ) {}

    public function updated(
        TrainingSession $session
    ): void {
        $meaningful = [
            'session_date',
            'start_time',
            'location',
            'duration_minutes',
            'session_type',
            'status',
        ];

        if (! $session->wasChanged($meaningful)) {
            return;
        }

        TrainingChanged::dispatch(
            (int) $session->organization_id,
            auth()->id(),
            $this->audience
                ->trainingChangedRecipients($session),
            [
                'training_session_id' => $session->id,
                'team_id' => $session->team_id,
                'changes' => array_intersect_key(
                    $session->getChanges(),
                    array_flip($meaningful)
                ),
                'title' => 'Training changed',
                'message' =>
                    'Training session details were updated for ' .
                    optional($session->session_date)->format('Y-m-d') .
                    '.',
                'url' =>
                    "/organizations/{$session->organization_id}/training/sessions/{$session->id}",
            ]
        );
    }
}
