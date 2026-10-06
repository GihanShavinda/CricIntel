<?php

namespace App\Http\Requests\Reports;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'report_type' => [
                'required',
                'string',
                Rule::in(array_keys(config('reports.types', []))),
            ],
            'format' => [
                'required',
                'string',
                Rule::in(array_keys(config('reports.formats', []))),
            ],
            'filters' => ['nullable', 'array'],
            'filters.player_id' => ['nullable', 'integer', 'min:1'],
            'filters.match_id' => ['nullable', 'integer', 'min:1'],
            'filters.team_id' => ['nullable', 'integer', 'min:1'],
            'filters.opponent_team_id' => ['nullable', 'integer', 'min:1'],
            'filters.training_session_id' => ['nullable', 'integer', 'min:1'],
            'filters.scouting_report_id' => ['nullable', 'integer', 'min:1'],
            'filters.tournament_id' => ['nullable', 'integer', 'min:1'],
            'filters.strategy_plan_id' => ['nullable', 'integer', 'min:1'],
            'filters.season_id' => ['nullable', 'integer', 'min:1'],
            'filters.venue_id' => ['nullable', 'integer', 'min:1'],
            'filters.format' => ['nullable', 'string', 'max:30'],
            'filters.phase' => [
                'nullable',
                'string',
                Rule::in(['all', 'powerplay', 'middle', 'death']),
            ],
            'filters.date_from' => ['nullable', 'date'],
            'filters.date_to' => ['nullable', 'date', 'after_or_equal:filters.date_from'],
        ];
    }
}
