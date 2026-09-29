<?php

namespace App\Http\Requests\Scouting;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConvertScoutingProfileRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'primary_role' => ['required','string','max:80'],
            'batting_style' => ['nullable','string','max:80'],
            'bowling_style' => ['nullable','string','max:100'],
            'fitness_status' => ['nullable','string','max:80'],
            'status' => ['nullable', Rule::in(['active','inactive'])],
            'notes' => ['nullable','string','max:5000'],
        ];
    }
}
