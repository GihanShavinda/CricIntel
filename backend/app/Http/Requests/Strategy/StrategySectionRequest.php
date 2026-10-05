<?php

namespace App\Http\Requests\Strategy;

use Illuminate\Foundation\Http\FormRequest;

class StrategySectionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'content' => ['nullable','string','max:30000'],
            'structured_data' => ['nullable','array'],
        ];
    }
}
