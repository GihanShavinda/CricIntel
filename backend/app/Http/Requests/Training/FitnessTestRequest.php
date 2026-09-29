<?php
namespace App\Http\Requests\Training;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FitnessTestRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'player_id'=>['required','integer','exists:players,id'],
            'training_session_id'=>['nullable','integer','exists:training_sessions,id'],
            'test_type'=>['required','string','max:80',Rule::in([
                'Yo-Yo Test','Sprint','Endurance','Strength','Custom',
            ])],
            'tested_at'=>['required','date'],
            'value'=>['nullable','numeric'],
            'unit'=>['nullable','string','max:40'],
            'measurements'=>['nullable','array'],
            'notes'=>['nullable','string','max:3000'],
        ];
    }
}
