<?php

namespace App\Http\Requests\Predictive;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TrainPredictiveModelsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'model_kinds' => [
                'required',
                'array',
                'min:1',
                'max:3',
            ],
            'model_kinds.*' => [
                'required',
                'string',
                'distinct',
                Rule::in([
                    'batter_score',
                    'bowler_economy',
                    'team_total',
                ]),
            ],
            'force' => [
                'nullable',
                'boolean',
            ],
        ];
    }
}
