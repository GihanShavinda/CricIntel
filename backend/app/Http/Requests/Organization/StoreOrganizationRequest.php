<?php

namespace App\Http\Requests\Organization;

use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Organization::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required','string','max:255'],
            'short_name' => ['nullable','string','max:50'],
            'logo' => ['nullable','image','mimes:jpg,jpeg,png,webp','max:4096'],
            'country' => ['nullable','string','max:100'],
            'timezone' => ['required','timezone'],
            'description' => ['nullable','string','max:5000'],
            'status' => ['required', Rule::in(['active','inactive','archived'])],
        ];
    }
}
