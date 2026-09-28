<?php

namespace App\Http\Requests\Organization;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('organization')) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes','required','string','max:255'],
            'short_name' => ['nullable','string','max:50'],
            'logo' => ['nullable','image','mimes:jpg,jpeg,png,webp','max:4096'],
            'country' => ['nullable','string','max:100'],
            'timezone' => ['sometimes','required','timezone'],
            'description' => ['nullable','string','max:5000'],
            'status' => ['sometimes','required', Rule::in(['active','inactive','archived'])],
        ];
    }
}
