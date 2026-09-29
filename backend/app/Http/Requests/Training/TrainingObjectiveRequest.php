<?php
namespace App\Http\Requests\Training;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TrainingObjectiveRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'player_id'=>['required','integer','exists:players,id'],
            'title'=>['required','string','max:180'],
            'weakness'=>['nullable','string','max:5000'],
            'statistic_scope'=>['nullable',Rule::in(['Batting','Bowling','Fielding','Team','Match'])],
            'metric_key'=>['nullable','string','max:100'],
            'observed_value'=>['nullable','numeric'],
            'target_value'=>['nullable','numeric'],
            'source_context'=>['nullable','array'],
            'status'=>['nullable',Rule::in(['Active','Completed','Paused','Cancelled'])],
            'target_date'=>['nullable','date'],
            'notes'=>['nullable','string','max:5000'],
        ];
    }
}
