<?php
namespace App\Services\Training;

use App\Models\Player;
use App\Models\TrainingObjective;

class TrainingStatisticsLinkService
{
    public function createObjective(
        int $organizationId,
        Player $player,
        int $actorId,
        array $data
    ): TrainingObjective {
        return TrainingObjective::query()->create([
            'organization_id'=>$organizationId,
            'player_id'=>$player->id,
            'created_by'=>$actorId,
            'title'=>$data['title'],
            'weakness'=>$data['weakness'] ?? null,
            'statistic_scope'=>$data['statistic_scope'] ?? null,
            'metric_key'=>$data['metric_key'] ?? null,
            'observed_value'=>$data['observed_value'] ?? null,
            'target_value'=>$data['target_value'] ?? null,
            'source_context'=>$data['source_context'] ?? null,
            'status'=>$data['status'] ?? 'Active',
            'target_date'=>$data['target_date'] ?? null,
            'notes'=>$data['notes'] ?? null,
        ]);
    }
}
