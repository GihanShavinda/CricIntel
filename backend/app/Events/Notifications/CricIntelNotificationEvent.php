<?php

namespace App\Events\Notifications;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

abstract class CricIntelNotificationEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $organizationId,
        public readonly ?int $actorId,
        public readonly array $recipientUserIds,
        public readonly array $data = []
    ) {}

    abstract public function type(): string;

    abstract public function title(): string;

    abstract public function message(): string;

    public function url(): ?string
    {
        return $this->data['url'] ?? null;
    }

    public function emailRecommended(): bool
    {
        return false;
    }

    public function payload(): array
    {
        return [
            'type' => $this->type(),
            'title' => $this->title(),
            'message' => $this->message(),
            'url' => $this->url(),
            'organization_id' => $this->organizationId,
            'actor_id' => $this->actorId,
            'data' => $this->data,
        ];
    }
}
