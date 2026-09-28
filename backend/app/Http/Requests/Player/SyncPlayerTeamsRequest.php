<?php
namespace App\Http\Requests\Player;

use Illuminate\Foundation\Http\FormRequest;

class SyncPlayerTeamsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('player')) ?? false;
    }

    public function rules(): array
    {
        return [
            'memberships' => ['required','array'],
            'memberships.*.team_id' => ['required','integer','exists:teams,id'],
            'memberships.*.jersey_number' => ['nullable','integer','min:0','max:999'],
            'memberships.*.joined_at' => ['nullable','date'],
            'memberships.*.left_at' => ['nullable','date'],
            'memberships.*.is_current' => ['required','boolean'],
        ];
    }
}
