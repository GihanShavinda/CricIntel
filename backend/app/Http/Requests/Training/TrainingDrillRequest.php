<?php
namespace App\Http\Requests\Training;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TrainingDrillRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'=>['required','string','max:150'],
            'category'=>['required','string','max:80'],
            'objective'=>['nullable','string','max:3000'],
            'duration_minutes'=>['nullable','integer','min:1','max:360'],
            'difficulty'=>['required',Rule::in(['Beginner','Intermediate','Advanced'])],
            'notes'=>['nullable','string','max:3000'],
            'is_active'=>['nullable','boolean'],
        ];
    }
}
