<?php

namespace App\Http\Requests\Scouting;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ScoutingMediaRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'media_type' => ['required', Rule::in(['Video URL','File'])],
            'video_url' => [
                Rule::requiredIf(fn () => $this->input('media_type') === 'Video URL'),
                'nullable',
                'url',
                'max:2048',
            ],
            'file' => [
                Rule::requiredIf(fn () => $this->input('media_type') === 'File'),
                'nullable',
                'file',
                'mimes:mp4,mov,avi,webm,jpg,jpeg,png,pdf',
                'max:102400',
            ],
            'caption' => ['nullable','string','max:255'],
        ];
    }
}
