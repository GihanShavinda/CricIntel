<?php

namespace App\Http\Requests\Notifications;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNotificationPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $types = array_keys(
            config('cricintel_notifications.types', [])
        );

        return [
            'preferences' => [
                'required',
                'array',
                'min:1',
            ],

            'preferences.*.event_type' => [
                'required',
                'string',
                Rule::in($types),
            ],

            'preferences.*.database_enabled' => [
                'required',
                'boolean',
            ],

            'preferences.*.email_enabled' => [
                'required',
                'boolean',
            ],

            'preferences.*.realtime_enabled' => [
                'required',
                'boolean',
            ],
        ];
    }
}
