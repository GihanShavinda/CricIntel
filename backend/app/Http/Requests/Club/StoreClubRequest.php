<?php

namespace App\Http\Requests\Club;

use App\Models\Club;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClubRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', [Club::class, $this->route('organization')]) ?? false;
    }

    public function rules(): array
    {
        $organizationId = $this->route('organization')->id;

        return [
            'name' => ['required','string','max:255'],
            'code' => [
                'nullable','string','max:30',
                Rule::unique('clubs','code')->where('organization_id', $organizationId),
            ],
            'logo' => ['nullable','image','mimes:jpg,jpeg,png,webp','max:4096'],
            'location' => ['nullable','string','max:255'],
            'founded_year' => ['nullable','integer','min:1700','max:'.now()->year],
            'description' => ['nullable','string','max:5000'],
        ];
    }
}
