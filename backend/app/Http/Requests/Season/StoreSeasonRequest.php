<?php

namespace App\Http\Requests\Season;

use App\Models\Season;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSeasonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', [Season::class, $this->route('organization')]) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required','string','max:100',
                Rule::unique('seasons','name')->where('organization_id', $this->route('organization')->id),
            ],
            'start_date' => ['required','date'],
            'end_date' => ['required','date','after_or_equal:start_date'],
            'status' => ['required', Rule::in(['planned','active','completed','archived'])],
        ];
    }
}
