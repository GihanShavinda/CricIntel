<?php
namespace App\Http\Requests\Training;

use Illuminate\Foundation\Http\FormRequest;

class PlayerAssessmentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'player_id'=>['required','integer','exists:players,id'],
            'training_session_id'=>['nullable','integer','exists:training_sessions,id'],
            'assessed_at'=>['required','date'],
            'technical_rating'=>['nullable','integer','min:1','max:10'],
            'tactical_rating'=>['nullable','integer','min:1','max:10'],
            'fitness_rating'=>['nullable','integer','min:1','max:10'],
            'attitude_rating'=>['nullable','integer','min:1','max:10'],
            'strengths'=>['nullable','string','max:5000'],
            'weaknesses'=>['nullable','string','max:5000'],
            'notes'=>['nullable','string','max:5000'],
        ];
    }
}
