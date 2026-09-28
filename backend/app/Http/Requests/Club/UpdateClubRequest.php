<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClubRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('club')) ?? false;
    }

    public function rules(): array
    {
        $club = $this->route('club');

        return [
            'name' => ['sometimes','required','string','max:255'],
            'code' => [
                'nullable','string','max:30',
                Rule::unique('clubs','code')
                    ->where('organization_id', $club->organization_id)
                    ->ignore($club->id),
            ],
            'logo' => ['nullable','image','mimes:jpg,jpeg,png,webp','max:4096'],
            'location' => ['nullable','string','max:255'],
            'founded_year' => ['nullable','integer','min:1700','max:'.now()->year],
            'description' => ['nullable','string','max:5000'],
        ];
    }
}
