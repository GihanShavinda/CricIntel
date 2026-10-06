<?php

namespace App\Listeners\Notifications;

use App\Events\Notifications\CricIntelNotificationEvent;
use App\Models\User;
use App\Notifications\CricIntelDomainNotification;

class SendCricIntelDomainNotification
{
    public function handle(
        CricIntelNotificationEvent $event
    ): void {
        $recipientIds = collect(
            $event->recipientUserIds
        )
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->reject(
                fn ($id) =>
                    $event->actorId !== null &&
                    $id === (int) $event->actorId &&
                    in_array(
                        $event->type(),
                        [
                            'analyst_mentioned',
                            'coach_comment_added',
                        ],
                        true
                    )
            )
            ->values();

        if ($recipientIds->isEmpty()) {
            return;
        }

        User::query()
            ->whereIn('id', $recipientIds)
            ->chunkById(
                100,
                function ($users) use ($event) {
                    foreach ($users as $user) {
                        $user->notify(
                            new CricIntelDomainNotification(
                                $event->payload(),
                                $event->emailRecommended()
                            )
                        );
                    }
                }
            );
    }
}
