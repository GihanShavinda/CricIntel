<?php

namespace App\Services\Player;

use App\Models\Player;
use App\Models\Organization;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PlayerService
{
    public function create(Organization $organization, array $data, ?UploadedFile $photo = null): Player
    {
        return DB::transaction(function () use ($organization, $data, $photo) {
            $positions = $data['positions'] ?? [];
            unset($data['positions']);

            if ($photo) {
                $data['photo'] = $photo->store('players', 'public');
            }

            $player = $organization->players()->create($data);

            foreach (array_values($positions) as $index => $position) {
                $player->positions()->create([
                    'position' => $position,
                    'priority' => $index + 1,
                ]);
            }

            return $player;
        });
    }

    public function update(Player $player, array $data, ?UploadedFile $photo = null): Player
    {
        return DB::transaction(function () use ($player, $data, $photo) {
            $hasPositions = array_key_exists('positions', $data);
            $positions = $data['positions'] ?? [];
            unset($data['positions']);

            if ($photo) {
                if ($player->photo) {
                    Storage::disk('public')->delete($player->photo);
                }
                $data['photo'] = $photo->store('players', 'public');
            }

            $player->update($data);

            if ($hasPositions) {
                $player->positions()->delete();

                foreach (array_values($positions) as $index => $position) {
                    $player->positions()->create([
                        'position' => $position,
                        'priority' => $index + 1,
                    ]);
                }
            }

            return $player->refresh();
        });
    }

    public function delete(Player $player): void
    {
        if ($player->photo) {
            Storage::disk('public')->delete($player->photo);
        }

        $player->delete();
    }
}
