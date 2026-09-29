<?php

namespace App\Http\Requests\Scouting;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ScoutingProfileRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'existing_player_id' => ['nullable','integer','exists:players,id'],
            'first_name' => ['required','string','max:100'],
            'last_name' => ['nullable','string','max:100'],
            'display_name' => ['required','string','max:160'],
            'date_of_birth' => ['nullable','date'],
            'nationality' => ['nullable','string','max:100'],
            'role' => ['nullable','string','max:80'],
            'batting_style' => ['nullable','string','max:80'],
            'bowling_style' => ['nullable','string','max:100'],
            'current_team' => ['nullable','string','max:160'],
            'current_competition' => ['nullable','string','max:160'],
            'source' => ['nullable','string','max:120'],
            'status' => ['nullable', Rule::in([
                'Watching',
                'Shortlisted',
                'Recommended',
                'Rejected',
                'Converted',
            ])],
            'summary' => ['nullable','string','max:5000'],
        ];
    }
}
