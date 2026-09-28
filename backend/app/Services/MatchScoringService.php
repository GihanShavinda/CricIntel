<?php

namespace App\Services;

use App\Models\CricketMatch;
use App\Models\Delivery;
use App\Models\Innings;
use App\Models\Over;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MatchScoringService
{
    private const FREE_HIT_ALLOWED_WICKETS = [
        'run_out',
        'obstructing_field',
        'hit_ball_twice',
    ];

    public function startMatch(CricketMatch $match, array $data = []): CricketMatch
    {
        if (! in_array($match->status, ['Scheduled', 'Delayed'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Only a scheduled or delayed match can be started.',
            ]);
        }

        $match->update([
            'toss_winner_id' => $data['toss_winner_id'] ?? $match->toss_winner_id,
            'toss_decision' => $data['toss_decision'] ?? $match->toss_decision,
            'max_overs' => $data['max_overs'] ?? $match->max_overs,
            'status' => 'In Progress',
        ]);

        return $match->refresh();
    }

    public function startInnings(CricketMatch $match, array $data): Innings
    {
        return DB::transaction(function () use ($match, $data) {
            $match = CricketMatch::query()->lockForUpdate()->findOrFail($match->id);

            if ($match->status !== 'In Progress') {
                throw ValidationException::withMessages([
                    'match' => 'Match must be in progress before starting an innings.',
                ]);
            }

            if ($match->innings()->where('status', 'In Progress')->exists()) {
                throw ValidationException::withMessages([
                    'innings' => 'Another innings is already in progress.',
                ]);
            }

            $number = (int) $data['innings_number'];

            if ($match->innings()->where('innings_number', $number)->exists()) {
                throw ValidationException::withMessages([
                    'innings_number' => 'This innings number already exists.',
                ]);
            }

            $target = null;

            if ($number === 2) {
                $first = $match->innings()->where('innings_number', 1)->first();

                if (! $first || $first->status !== 'Completed') {
                    throw ValidationException::withMessages([
                        'innings_number' => 'First innings must be completed before starting the chase.',
                    ]);
                }

                $target = $first->runs + 1;
                $match->update(['target_runs' => $target]);
            }

            return $match->innings()->create([
                'batting_team_id' => $data['batting_team_id'],
                'bowling_team_id' => $data['bowling_team_id'],
                'innings_number' => $number,
                'runs' => 0,
                'wickets' => 0,
                'legal_balls' => 0,
                'overs_completed' => 0,
                'status' => 'In Progress',
                'striker_id' => $data['striker_id'],
                'non_striker_id' => $data['non_striker_id'],
                'current_bowler_id' => $data['bowler_id'],
                'free_hit_next' => false,
                'target' => $target,
            ]);
        });
    }

    public function recordDelivery(Innings $innings, array $data): Delivery
    {
        return DB::transaction(function () use ($innings, $data) {
            $innings = Innings::query()->lockForUpdate()->findOrFail($innings->id);
            $match = CricketMatch::query()->lockForUpdate()->findOrFail($innings->match_id);

            if ($innings->status !== 'In Progress') {
                throw ValidationException::withMessages([
                    'innings' => 'Deliveries can only be recorded for an innings in progress.',
                ]);
            }

            $extraType = $data['extra_type'] ?? 'none';
            $runsOffBat = (int) ($data['runs_off_bat'] ?? 0);
            $extraRuns = (int) ($data['extra_runs'] ?? 0);

            $this->validateRunCombination($extraType, $runsOffBat, $extraRuns);

            $isLegal = ! in_array($extraType, ['wide', 'no_ball'], true);
            $isFreeHit = (bool) $innings->free_hit_next;
            $wicket = (bool) ($data['wicket'] ?? false);
            $wicketType = $data['wicket_type'] ?? null;

            if ($wicket && $isFreeHit && ! in_array($wicketType, self::FREE_HIT_ALLOWED_WICKETS, true)) {
                $wicket = false;
            }

            $overNumber = intdiv($innings->legal_balls, 6) + 1;

            $over = Over::query()
                ->where('innings_id', $innings->id)
                ->where('over_number', $overNumber)
                ->lockForUpdate()
                ->first();

            if (! $over) {
                $over = $innings->overs()->create([
                    'over_number' => $overNumber,
                    'bowler_id' => $data['bowler_id'],
                    'legal_balls' => 0,
                    'runs' => 0,
                    'wickets' => 0,
                    'status' => 'In Progress',
                ]);
            } elseif ((int) $over->bowler_id !== (int) $data['bowler_id']) {
                throw ValidationException::withMessages([
                    'bowler_id' => 'The bowler cannot change before the current over is complete.',
                ]);
            }

            $sequence = ((int) $innings->deliveries()->max('sequence_number')) + 1;
            $ballNumber = min(6, $over->legal_balls + 1);
            $totalRuns = $runsOffBat + $extraRuns;

            $delivery = $innings->deliveries()->create([
                'over_id' => $over->id,
                'sequence_number' => $sequence,
                'ball_number' => $ballNumber,
                'bowler_id' => $data['bowler_id'],
                'batter_id' => $data['batter_id'],
                'non_striker_id' => $data['non_striker_id'],
                'runs_off_bat' => $runsOffBat,
                'extra_runs' => $extraRuns,
                'total_runs' => $totalRuns,
                'extra_type' => $extraType,
                'is_legal' => $isLegal,
                'is_free_hit' => $isFreeHit,
                'wicket' => $wicket,
                'wicket_type' => $wicket ? $wicketType : null,
                'dismissed_player_id' => $wicket ? ($data['dismissed_player_id'] ?? null) : null,
                'fielder_id' => $wicket ? ($data['fielder_id'] ?? null) : null,
                'shot_type' => $data['shot_type'] ?? null,
                'delivery_type' => $data['delivery_type'] ?? null,
                'pitch_zone' => $data['pitch_zone'] ?? null,
                'ball_speed' => $data['ball_speed'] ?? null,
                'delivery_timestamp' => $data['timestamp'] ?? now(),
            ]);

            $innings->runs += $totalRuns;
            $over->runs += $totalRuns;

            if ($isLegal) {
                $innings->legal_balls++;
                $over->legal_balls++;
            }

            if ($wicket) {
                if (empty($data['dismissed_player_id'])) {
                    throw ValidationException::withMessages([
                        'dismissed_player_id' => 'Dismissed player is required when recording a wicket.',
                    ]);
                }

                $innings->wickets++;
                $over->wickets++;

                $delivery->wicketRecord()->create([
                    'innings_id' => $innings->id,
                    'dismissed_player_id' => $data['dismissed_player_id'],
                    'wicket_type' => $wicketType,
                    'bowler_id' => $this->bowlerGetsCredit($wicketType) ? $data['bowler_id'] : null,
                    'fielder_id' => $data['fielder_id'] ?? null,
                    'runs_at_wicket' => $innings->runs,
                    'wicket_number' => $innings->wickets,
                ]);
            }

            $innings->free_hit_next = $extraType === 'no_ball';

            [$nextStriker, $nextNonStriker] = $this->nextBatters(
                (int) $data['batter_id'],
                (int) $data['non_striker_id'],
                $extraType,
                $runsOffBat,
                $extraRuns,
                $isLegal,
                $over->legal_balls
            );

            if ($wicket && (int) $data['dismissed_player_id'] === $nextStriker) {
                $nextStriker = null;
            }
            if ($wicket && (int) $data['dismissed_player_id'] === $nextNonStriker) {
                $nextNonStriker = null;
            }

            $innings->striker_id = $nextStriker;
            $innings->non_striker_id = $nextNonStriker;
            $innings->current_bowler_id = $data['bowler_id'];
            $innings->overs_completed = $this->oversNotation($innings->legal_balls);

            if ($over->legal_balls >= 6) {
                $over->status = 'Completed';
            }

            $over->save();
            $innings->save();

            $this->autoCompleteInningsIfNeeded($match, $innings);

            return $delivery->fresh(['wicketRecord']);
        });
    }

    public function undoLatestDelivery(Innings $innings): ?Delivery
    {
        return DB::transaction(function () use ($innings) {
            $innings = Innings::query()->lockForUpdate()->findOrFail($innings->id);

            $latest = $innings->deliveries()
                ->orderByDesc('sequence_number')
                ->lockForUpdate()
                ->first();

            if (! $latest) {
                return null;
            }

            $snapshot = clone $latest;
            $overId = $latest->over_id;
            $latest->delete();

            $this->recalculateInnings($innings);

            $over = Over::find($overId);
            if ($over && ! $over->deliveries()->exists()) {
                $over->delete();
            }

            return $snapshot;
        });
    }

    public function completeInnings(Innings $innings): Innings
    {
        return DB::transaction(function () use ($innings) {
            $innings = Innings::query()->lockForUpdate()->findOrFail($innings->id);
            $innings->update(['status' => 'Completed']);

            $match = $innings->match;
            if ($innings->innings_number === 1) {
                $match->update(['target_runs' => $innings->runs + 1]);
            }

            return $innings->refresh();
        });
    }

    public function completeMatch(CricketMatch $match, array $data = []): CricketMatch
    {
        return DB::transaction(function () use ($match, $data) {
            $match = CricketMatch::query()->lockForUpdate()->findOrFail($match->id);
            $innings = $match->innings()->orderBy('innings_number')->get();

            $winner = $data['winner_team_id'] ?? null;
            $resultType = $data['result_type'] ?? null;

            if ($innings->count() >= 2 && ! $winner && ! $resultType) {
                $first = $innings->first();
                $second = $innings->get(1);

                if ($second->runs > $first->runs) {
                    $winner = $second->batting_team_id;
                    $resultType = 'Win by wickets';
                } elseif ($first->runs > $second->runs) {
                    $winner = $first->batting_team_id;
                    $resultType = 'Win by runs';
                } else {
                    $resultType = 'Tie';
                }
            }

            $match->update([
                'status' => 'Completed',
                'winner_team_id' => $winner,
                'result_type' => $resultType,
                'player_of_match_id' => $data['player_of_match_id'] ?? $match->player_of_match_id,
            ]);

            return $match->refresh();
        });
    }

    public function score(Innings $innings): array
    {
        return [
            'runs' => $innings->runs,
            'wickets' => $innings->wickets,
            'overs' => $this->oversNotation($innings->legal_balls),
            'display' => "{$innings->runs}/{$innings->wickets}",
            'target' => $innings->target,
            'runs_required' => $innings->target ? max(0, $innings->target - $innings->runs) : null,
        ];
    }

    private function validateRunCombination(string $extraType, int $runsOffBat, int $extraRuns): void
    {
        if (in_array($extraType, ['wide', 'no_ball'], true) && $extraRuns < 1) {
            throw ValidationException::withMessages([
                'extra_runs' => 'A wide or no-ball must include at least one extra run.',
            ]);
        }

        if ($extraType === 'none' && $extraRuns !== 0) {
            throw ValidationException::withMessages([
                'extra_runs' => 'Extra runs must be zero when extra type is none.',
            ]);
        }

        if (in_array($extraType, ['bye', 'leg_bye', 'wide'], true) && $runsOffBat > 0) {
            throw ValidationException::withMessages([
                'runs_off_bat' => 'Runs off the bat cannot be combined with this extra type.',
            ]);
        }
    }

    private function autoCompleteInningsIfNeeded(CricketMatch $match, Innings $innings): void
    {
        $allOut = $innings->wickets >= 10;
        $maxOversReached = $match->max_overs && $innings->legal_balls >= ($match->max_overs * 6);
        $targetReached = $innings->target && $innings->runs >= $innings->target;

        if (! ($allOut || $maxOversReached || $targetReached)) {
            return;
        }

        $innings->status = 'Completed';
        $innings->save();

        if ($innings->innings_number === 1) {
            $match->target_runs = $innings->runs + 1;
            $match->save();
        }

        if ($innings->innings_number >= 2) {
            $this->completeMatch($match);
        }
    }

    private function recalculateInnings(Innings $innings): void
    {
        $deliveries = $innings->deliveries()->orderBy('sequence_number')->get();

        $innings->runs = $deliveries->sum('total_runs');
        $innings->wickets = $deliveries->where('wicket', true)->count();
        $innings->legal_balls = $deliveries->where('is_legal', true)->count();
        $innings->overs_completed = $this->oversNotation($innings->legal_balls);
        $innings->free_hit_next = $deliveries->last()?->extra_type === 'no_ball';
        $innings->status = 'In Progress';

        foreach ($innings->overs as $over) {
            $overDeliveries = $over->deliveries;
            $over->runs = $overDeliveries->sum('total_runs');
            $over->wickets = $overDeliveries->where('wicket', true)->count();
            $over->legal_balls = $overDeliveries->where('is_legal', true)->count();
            $over->status = $over->legal_balls >= 6 ? 'Completed' : 'In Progress';
            $over->save();
        }

        $latest = $deliveries->last();

        if ($latest) {
            $over = $latest->over;
            [$striker, $nonStriker] = $this->nextBatters(
                $latest->batter_id,
                $latest->non_striker_id,
                $latest->extra_type,
                $latest->runs_off_bat,
                $latest->extra_runs,
                $latest->is_legal,
                $over?->legal_balls ?? 0
            );
            $innings->striker_id = $striker;
            $innings->non_striker_id = $nonStriker;
            $innings->current_bowler_id = $latest->bowler_id;
        }

        $innings->save();

        $match = $innings->match;
        if ($match->status === 'Completed') {
            $match->update([
                'status' => 'In Progress',
                'winner_team_id' => null,
                'result_type' => null,
            ]);
        }
    }

    private function nextBatters(
        int $striker,
        int $nonStriker,
        string $extraType,
        int $runsOffBat,
        int $extraRuns,
        bool $isLegal,
        int $legalBallsInOver
    ): array {
        $runningRuns = match ($extraType) {
            'bye', 'leg_bye' => $extraRuns,
            'wide' => max(0, $extraRuns - 1),
            'no_ball' => $runsOffBat > 0 ? $runsOffBat : max(0, $extraRuns - 1),
            default => $runsOffBat,
        };

        if ($runningRuns % 2 === 1) {
            [$striker, $nonStriker] = [$nonStriker, $striker];
        }

        if ($isLegal && $legalBallsInOver === 6) {
            [$striker, $nonStriker] = [$nonStriker, $striker];
        }

        return [$striker, $nonStriker];
    }

    private function bowlerGetsCredit(?string $wicketType): bool
    {
        return ! in_array($wicketType, ['run_out', 'retired_hurt', 'obstructing_field'], true);
    }

    private function oversNotation(int $legalBalls): float
    {
        return (float) (intdiv($legalBalls, 6).'.'.($legalBalls % 6));
    }
}
