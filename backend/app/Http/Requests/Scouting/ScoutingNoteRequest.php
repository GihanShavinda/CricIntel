<?php

namespace App\Http\Requests\Scouting;

use Illuminate\Foundation\Http\FormRequest;

class ScoutingNoteRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'scouting_report_id' => ['nullable','integer','exists:scouting_reports,id'],
            'note' => ['required','string','max:5000'],
            'is_private' => ['nullable','boolean'],
        ];
    }
}
