<?php

namespace App\Http\Requests\Strategy;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StrategyAttachmentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'source_type' => ['nullable', Rule::in(['plan','note','comment'])],
            'source_id' => ['nullable','integer'],
            'attachment_type' => ['required', Rule::in([
                'player',
                'match',
                'scouting_report',
                'scouting_media',
                'file',
                'url',
            ])],
            'entity_id' => ['nullable','integer'],
            'label' => ['nullable','string','max:220'],
            'external_url' => [
                Rule::requiredIf(fn () => $this->input('attachment_type') === 'url'),
                'nullable',
                'url',
                'max:2048',
            ],
            'file' => [
                Rule::requiredIf(fn () => $this->input('attachment_type') === 'file'),
                'nullable',
                'file',
                'max:102400',
            ],
        ];
    }
}
