<?php

namespace App\Http\Requests\Strategy;

use Illuminate\Foundation\Http\FormRequest;

class TacticalNoteRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'strategy_section_id' => ['nullable','integer','exists:strategy_sections,id'],
            'title' => ['nullable','string','max:180'],
            'body' => ['required','string','max:20000'],
            'mention_user_ids' => ['nullable','array','max:20'],
            'mention_user_ids.*' => ['integer','distinct','exists:users,id'],
        ];
    }
}
