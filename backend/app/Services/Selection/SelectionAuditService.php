<?php

namespace App\Services\Selection;

class SelectionAuditService
{
    public function record(string $action, int $actorId, array $payload = []): void
    {
        if (!class_exists(\App\Models\AuditLog::class)) return;
        try {
            \App\Models\AuditLog::query()->create(['user_id' => $actorId, 'action' => $action, 'metadata' => $payload]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
