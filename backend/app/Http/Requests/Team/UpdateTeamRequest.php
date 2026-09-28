<?php

namespace App\Http\Requests\Team;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('team')) ?? false;
    }

    public function rules(): array
    {
        $organizationId = $this->route('organization')->id;

        return [
            'club_id' => [
                'sometimes','required','integer',
                Rule::exists('clubs','id')->where('organization_id', $organizationId),
            ],
            'name' => ['sometimes','required','string','max:255'],
            'short_name' => ['nullable','string','max:50'],
            'gender' => ['nullable', Rule::in(['male','female','mixed','other'])],
            'category' => ['nullable','string','max:50'],
            'age_group' => ['nullable','string','max:50'],
            'format_preferences' => ['nullable','array'],
            'format_preferences.*' => ['string','max:30'],
            'home_ground' => ['nullable','string','max:255'],
            'status' => ['sometimes','required', Rule::in(['active','inactive','archived'])],
        ];
    }
}
