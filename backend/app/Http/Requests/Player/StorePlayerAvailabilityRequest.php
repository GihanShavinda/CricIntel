<?php
namespace App\Http\Requests\Player;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlayerAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('player')) ?? false;
    }

    public function rules(): array
    {
        return [
            'available_from' => ['required','date'],
            'available_to' => ['nullable','date','after_or_equal:available_from'],
            'reason' => ['nullable','string','max:2000'],
            'status' => ['required', Rule::in(['Available','Unavailable','Partial'])],
        ];
    }
}
