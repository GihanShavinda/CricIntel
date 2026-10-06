<?php

namespace App\Observers\Notifications;

use App\Events\Notifications\AnalystMentioned;
use App\Models\Mention;
use App\Services\Notifications\NotificationAudienceService;

class MentionObserver
{
    public function __construct(
        private readonly NotificationAudienceService $audience
    ) {}

    public function created(Mention $mention): void
    {
        $mention->loadMissing([
            'plan',
            'mentioner',
            'mentionedUser',
        ]);

        AnalystMentioned::dispatch(
            (int) $mention->plan->organization_id,
            $mention->mentioned_by,
            $this->audience
                ->mentionRecipients($mention),
            [
                'strategy_plan_id' =>
                    $mention->strategy_plan_id,
                'source_type' =>
                    $mention->source_type,
                'source_id' =>
                    $mention->source_id,
                'title' => 'You were mentioned',
                'message' =>
                    ($mention->mentioner?->name ?? 'A CricIntel user') .
                    ' mentioned you in a tactical discussion.',
                'url' =>
                    "/organizations/{$mention->plan->organization_id}/strategy/{$mention->strategy_plan_id}",
            ]
        );
    }
}
