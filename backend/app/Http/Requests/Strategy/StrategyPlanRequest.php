<?php

namespace App\Http\Requests\Strategy;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StrategyPlanRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'match_id' => ['required','integer','exists:matches,id'],
            'opponent_team_id' => ['nullable','integer','exists:teams,id'],
            'venue_id' => ['nullable','integer','exists:venues,id'],
            'title' => ['required','string','max:180'],
            'status' => ['nullable', Rule::in(['Draft','Active','Archived'])],
            'summary' => ['nullable','string','max:5000'],
        ];
    }
}
