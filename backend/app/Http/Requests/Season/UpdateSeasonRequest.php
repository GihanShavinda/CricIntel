<?php

namespace App\Http\Requests\Season;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSeasonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('season')) ?? false;
    }

    public function rules(): array
    {
        $season = $this->route('season');

        return [
            'name' => [
                'sometimes','required','string','max:100',
                Rule::unique('seasons','name')
                    ->where('organization_id', $season->organization_id)
                    ->ignore($season->id),
            ],
            'start_date' => ['sometimes','required','date'],
            'end_date' => ['sometimes','required','date'],
            'status' => ['sometimes','required', Rule::in(['planned','active','completed','archived'])],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $season = $this->route('season');
            $start = $this->input('start_date', $season->start_date?->toDateString());
            $end = $this->input('end_date', $season->end_date?->toDateString());

            if ($start && $end && strtotime($end) < strtotime($start)) {
                $validator->errors()->add('end_date', 'The end date must be after or equal to the start date.');
            }
        });
    }
}
