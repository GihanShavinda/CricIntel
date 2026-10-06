<?php

namespace App\Http\Requests\NlAnalytics;

use Illuminate\Foundation\Http\FormRequest;

class RunNaturalLanguageAnalyticsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'query' => [
                'required',
                'string',
                'min:3',
                'max:' . config('nl_analytics.max_query_length', 800),
            ],

            /*
             * Optional explicit values let the UI disambiguate names without
             * changing the controlled analytics vocabulary.
             */
            'team_id' => ['nullable', 'integer'],
            'season_id' => ['nullable', 'integer'],
            'opponent_team_id' => ['nullable', 'integer'],
            'venue_id' => ['nullable', 'integer'],
            'format' => ['nullable', 'string', 'max:50'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ];
    }
}
