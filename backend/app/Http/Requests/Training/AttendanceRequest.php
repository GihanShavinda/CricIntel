<?php
namespace App\Http\Requests\Training;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttendanceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'entries'=>['required','array'],
            'entries.*.player_id'=>['required','integer','distinct','exists:players,id'],
            'entries.*.status'=>['required',Rule::in(['Present','Absent','Late','Excused'])],
            'entries.*.arrival_time'=>['nullable','date_format:H:i'],
            'entries.*.notes'=>['nullable','string','max:2000'],
        ];
    }
}
