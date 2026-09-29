<?php
namespace App\Http\Requests\Training;

use Illuminate\Foundation\Http\FormRequest;

class SessionPlayersRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'player_ids'=>['required','array'],
            'player_ids.*'=>['integer','distinct','exists:players,id'],
        ];
    }
}
