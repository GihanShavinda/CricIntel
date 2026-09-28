<?php

namespace App\Http\Requests\Analytics;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AnalyticsFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'team_id' => ['nullable', 'integer', 'exists:teams,id'],
            'season_id' => ['nullable', 'integer', 'exists:seasons,id'],
            'tournament_id' => ['nullable', 'integer', 'exists:tournaments,id'],
            'opponent_id' => ['nullable', 'integer', 'exists:teams,id'],
            'venue_id' => ['nullable', 'integer', 'exists:venues,id'],
            'format' => ['nullable', 'string', 'max:30'],
            'batting_position' => ['nullable', 'integer', 'min:1', 'max:11'],
            'bowling_type' => ['nullable', Rule::in(['spin', 'pace'])],
            'phase' => ['nullable', Rule::in(['powerplay', 'middle', 'death'])],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    public function filters(): array
    {
        return array_filter(
            $this->validated(),
            static fn ($value) => $value !== null && $value !== ''
        );
    }
}
