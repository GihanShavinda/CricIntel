<?php

namespace App\Http\Requests\Predictive;

use Illuminate\Foundation\Http\FormRequest;

class PredictionContextRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'opponent_team_id' => [
                'nullable',
                'integer',
                'exists:teams,id',
            ],
            'venue_id' => [
                'nullable',
                'integer',
                'exists:venues,id',
            ],
            'max_overs' => [
                'nullable',
                'integer',
                'min:1',
                'max:450',
            ],
        ];
    }
}
