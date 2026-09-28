<?php

namespace App\Http\Requests\Analytics;

use Illuminate\Foundation\Http\FormRequest;

class PlayerComparisonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'player_a' => ['required', 'integer', 'different:player_b', 'exists:players,id'],
            'player_b' => ['required', 'integer', 'different:player_a', 'exists:players,id'],
            'season_id' => ['nullable', 'integer', 'exists:seasons,id'],
            'tournament_id' => ['nullable', 'integer', 'exists:tournaments,id'],
            'opponent_id' => ['nullable', 'integer', 'exists:teams,id'],
            'venue_id' => ['nullable', 'integer', 'exists:venues,id'],
            'format' => ['nullable', 'string', 'max:30'],
            'phase' => ['nullable', 'in:powerplay,middle,death'],
            'bowling_type' => ['nullable', 'in:spin,pace'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ];
    }

    public function filters(): array
    {
        $validated = $this->validated();
        unset($validated['player_a'], $validated['player_b']);

        return array_filter(
            $validated,
            static fn ($value) => $value !== null && $value !== ''
        );
    }
}
