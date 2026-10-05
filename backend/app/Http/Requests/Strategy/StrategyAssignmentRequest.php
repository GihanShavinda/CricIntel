<?php

namespace App\Http\Requests\Strategy;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StrategyAssignmentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'strategy_section_id' => ['nullable','integer','exists:strategy_sections,id'],
            'assigned_to' => ['required','integer','exists:users,id'],
            'title' => ['required','string','max:180'],
            'description' => ['nullable','string','max:5000'],
            'status' => ['nullable', Rule::in(['Todo','In Progress','Done'])],
            'due_at' => ['nullable','date'],
        ];
    }
}
