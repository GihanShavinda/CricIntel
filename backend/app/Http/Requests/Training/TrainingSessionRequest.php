<?php
namespace App\Http\Requests\Training;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TrainingSessionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'team_id'=>['required','integer','exists:teams,id'],
            'coach_id'=>['nullable','integer','exists:users,id'],
            'session_date'=>['required','date'],
            'start_time'=>['nullable','date_format:H:i'],
            'location'=>['nullable','string','max:180'],
            'duration_minutes'=>['required','integer','min:1','max:720'],
            'session_type'=>['required','string','max:80'],
            'status'=>['nullable',Rule::in(['Scheduled','In Progress','Completed','Cancelled'])],
            'notes'=>['nullable','string','max:5000'],
            'drill_ids'=>['nullable','array'],
            'drill_ids.*'=>['integer','distinct','exists:training_drills,id'],
        ];
    }
}
