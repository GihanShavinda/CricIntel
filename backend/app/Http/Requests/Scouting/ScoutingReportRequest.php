<?php

namespace App\Http\Requests\Scouting;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ScoutingReportRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'competition' => ['nullable','string','max:160'],
            'report_date' => ['required','date'],
            'observed_role' => ['nullable','string','max:80'],
            'strengths' => ['nullable','string','max:5000'],
            'weaknesses' => ['nullable','string','max:5000'],
            'potential' => ['nullable','integer','min:1','max:10'],
            'overall_recommendation' => ['required', Rule::in([
                'Highly Recommend',
                'Recommend',
                'Monitor',
                'Do Not Recommend',
            ])],
            'notes' => ['nullable','string','max:5000'],
            'technical_rating' => ['required','integer','min:1','max:10'],
            'tactical_rating' => ['required','integer','min:1','max:10'],
            'physical_rating' => ['required','integer','min:1','max:10'],
            'fielding_rating' => ['required','integer','min:1','max:10'],
            'mental_decision_rating' => ['required','integer','min:1','max:10'],
        ];
    }
}
