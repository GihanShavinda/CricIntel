<?php
namespace App\Http\Requests\Training;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DevelopmentPlanRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'player_id'=>['required','integer','exists:players,id'],
            'title'=>['required','string','max:180'],
            'weakness'=>['nullable','string','max:5000'],
            'objectives'=>['nullable','array'],
            'objectives.*'=>['string','max:1000'],
            'objective_ids'=>['nullable','array'],
            'objective_ids.*'=>['integer','distinct','exists:training_objectives,id'],
            'drill_ids'=>['nullable','array'],
            'drill_ids.*'=>['integer','distinct','exists:training_drills,id'],
            'start_date'=>['required','date'],
            'target_date'=>['nullable','date','after_or_equal:start_date'],
            'status'=>['nullable',Rule::in(['Active','Completed','Paused','Cancelled'])],
            'review_notes'=>['nullable','string','max:5000'],
        ];
    }
}
