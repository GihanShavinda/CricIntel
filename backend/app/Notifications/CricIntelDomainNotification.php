<?php

namespace App\Notifications;

use App\Services\Notifications\NotificationPreferenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CricIntelDomainNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries;
    public int $timeout;

    public function __construct(
        private readonly array $payload,
        private readonly bool $emailRecommended = false
    ) {
        $this->onQueue(
            config(
                'cricintel_notifications.queue',
                'notifications'
            )
        );

        // Domain observers may run inside database transactions.
        // Queue notification delivery only after the transaction commits.
        $this->afterCommit();

        $this->tries = config(
            'cricintel_notifications.tries',
            3
        );

        $this->timeout = config(
            'cricintel_notifications.timeout',
            30
        );
    }

    public function backoff(): array
    {
        return config(
            'cricintel_notifications.backoff',
            [30, 120, 300]
        );
    }

    public function via(object $notifiable): array
    {
        return app(
            NotificationPreferenceService::class
        )->channelsFor(
            $notifiable,
            $this->payload['type'],
            $this->emailRecommended
        );
    }

    public function toDatabase(object $notifiable): array
    {
        return $this->payload;
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            ...$this->payload,
            'created_at' => now()->toIso8601String(),
        ]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage())
            ->subject(
                '[CricIntel] ' .
                ($this->payload['title'] ?? 'Notification')
            )
            ->greeting(
                'Hello ' .
                ($notifiable->name ?? 'CricIntel user') .
                ','
            )
            ->line(
                $this->payload['message'] ??
                'You have a new CricIntel notification.'
            );

        if (! empty($this->payload['url'])) {
            $frontend = rtrim(
                (string) config(
                    'app.frontend_url',
                    env(
                        'FRONTEND_URL',
                        'http://localhost:5173'
                    )
                ),
                '/'
            );

            $mail->action(
                'Open CricIntel',
                $frontend .
                $this->payload['url']
            );
        }

        return $mail->line(
            'This message was generated from an event recorded in CricIntel.'
        );
    }

    public function toArray(object $notifiable): array
    {
        return $this->payload;
    }
}
