<?php

namespace App\Http\Requests\Strategy;

use Illuminate\Foundation\Http\FormRequest;

class StrategyCommentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'body' => ['required','string','max:10000'],
            'mention_user_ids' => ['nullable','array','max:20'],
            'mention_user_ids.*' => ['integer','distinct','exists:users,id'],
        ];
    }
}
