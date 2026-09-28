<?php

namespace App\Http\Requests\Team;

use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', [Team::class, $this->route('organization')]) ?? false;
    }

    public function rules(): array
    {
        $organizationId = $this->route('organization')->id;

        return [
            'club_id' => [
                'required','integer',
                Rule::exists('clubs','id')->where('organization_id', $organizationId),
            ],
            'name' => ['required','string','max:255'],
            'short_name' => ['nullable','string','max:50'],
            'gender' => ['nullable', Rule::in(['male','female','mixed','other'])],
            'category' => ['nullable','string','max:50'],
            'age_group' => ['nullable','string','max:50'],
            'format_preferences' => ['nullable','array'],
            'format_preferences.*' => ['string','max:30'],
            'home_ground' => ['nullable','string','max:255'],
            'status' => ['required', Rule::in(['active','inactive','archived'])],
        ];
    }
}
