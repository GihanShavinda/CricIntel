<?php

namespace App\Http\Requests\StrategyAi;

use Illuminate\Foundation\Http\FormRequest;

class AskStrategyAssistantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'question' => [
                'required',
                'string',
                'min:3',
                'max:' . config('strategy_ai.max_question_length', 1200),
            ],
        ];
    }
}
