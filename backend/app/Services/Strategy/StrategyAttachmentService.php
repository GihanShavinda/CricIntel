<?php

namespace App\Services\Strategy;

use App\Models\CricketMatch;
use App\Models\Player;
use App\Models\ScoutingMedia;
use App\Models\ScoutingReport;
use App\Models\StrategyAttachment;
use App\Models\StrategyPlan;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class StrategyAttachmentService
{
    public function create(
        StrategyPlan $plan,
        int $userId,
        array $data,
        ?UploadedFile $file = null
    ): StrategyAttachment {
        $this->validateEntity(
            $plan,
            $data['attachment_type'],
            isset($data['entity_id']) ? (int) $data['entity_id'] : null
        );

        $payload = [
            'strategy_plan_id' => $plan->id,
            'uploaded_by' => $userId,
            'source_type' => $data['source_type'] ?? 'plan',
            'source_id' => $data['source_id'] ?? $plan->id,
            'attachment_type' => $data['attachment_type'],
            'entity_id' => $data['entity_id'] ?? null,
            'label' => $data['label'] ?? null,
            'external_url' => $data['external_url'] ?? null,
        ];

        if ($data['attachment_type'] === 'file' && $file) {
            $path = $file->store(
                "strategy/{$plan->organization_id}/plans/{$plan->id}",
                'public'
            );

            $payload += [
                'disk' => 'public',
                'path' => $path,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
            ];
        }

        return StrategyAttachment::query()->create($payload);
    }

    public function delete(StrategyAttachment $attachment): void
    {
        if ($attachment->disk && $attachment->path) {
            Storage::disk($attachment->disk)->delete($attachment->path);
        }

        $attachment->delete();
    }

    private function validateEntity(
        StrategyPlan $plan,
        string $type,
        ?int $entityId
    ): void {
        if (in_array($type, ['file', 'url'], true)) {
            return;
        }

        if (! $entityId) {
            throw ValidationException::withMessages([
                'entity_id' => 'An entity ID is required for this attachment type.',
            ]);
        }

        $valid = match ($type) {
            'player' => Player::query()
                ->whereKey($entityId)
                ->where('organization_id', $plan->organization_id)
                ->exists(),

            'match' => CricketMatch::query()
                ->whereKey($entityId)
                ->where('organization_id', $plan->organization_id)
                ->exists(),

            'scouting_report' => ScoutingReport::query()
                ->whereKey($entityId)
                ->whereHas('profile', fn ($q) =>
                    $q->where('organization_id', $plan->organization_id)
                )
                ->exists(),

            'scouting_media' => ScoutingMedia::query()
                ->whereKey($entityId)
                ->whereHas('report.profile', fn ($q) =>
                    $q->where('organization_id', $plan->organization_id)
                )
                ->exists(),

            default => false,
        };

        if (! $valid) {
            throw ValidationException::withMessages([
                'entity_id' =>
                    'The attached entity must belong to the strategy organization.',
            ]);
        }
    }
}
