<?php

namespace App\Services\Notifications;

use App\Models\NotificationPreference;
use App\Models\User;

class NotificationPreferenceService
{
    public function channelsFor(
        User $user,
        string $eventType,
        bool $emailRecommended
    ): array {
        $definition = config(
            "cricintel_notifications.types.{$eventType}",
            []
        );

        $preference = NotificationPreference::query()
            ->where('user_id', $user->id)
            ->where('event_type', $eventType)
            ->first();

        $databaseEnabled =
            $preference?->database_enabled ?? true;

        $realtimeEnabled =
            $preference?->realtime_enabled ??
            ($definition['realtime_default'] ?? true);

        $emailEnabled =
            $preference?->email_enabled ??
            (
                $emailRecommended &&
                ($definition['email_default'] ?? false)
            );

        return array_values(array_filter([
            $databaseEnabled ? 'database' : null,
            $realtimeEnabled ? 'broadcast' : null,
            $emailEnabled ? 'mail' : null,
        ]));
    }

    public function defaultsFor(User $user): array
    {
        return collect(
            config('cricintel_notifications.types', [])
        )
            ->map(function (
                array $definition,
                string $eventType
            ) use ($user) {
                $saved = NotificationPreference::query()
                    ->where('user_id', $user->id)
                    ->where('event_type', $eventType)
                    ->first();

                return [
                    'event_type' => $eventType,
                    'label' =>
                        $definition['label'] ??
                        $eventType,
                    'database_enabled' =>
                        $saved?->database_enabled ??
                        true,
                    'email_enabled' =>
                        $saved?->email_enabled ??
                        (
                            $definition['email_default'] ??
                            false
                        ),
                    'realtime_enabled' =>
                        $saved?->realtime_enabled ??
                        (
                            $definition['realtime_default'] ??
                            true
                        ),
                ];
            })
            ->values()
            ->all();
    }
}
