<?php
namespace App\Http\Requests\Player;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlayerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('player')) ?? false;
    }

    public function rules(): array
    {
        $self = $this->route('player')->user_id === $this->user()?->id;

        if ($self && ! $this->user()->hasAnyRole(['Administrator','Coach','Team Manager'])) {
            return [
                'display_name' => ['sometimes','required','string','max:150'],
                'nationality' => ['nullable','string','max:100'],
                'photo' => ['nullable','image','mimes:jpg,jpeg,png,webp','max:4096'],
            ];
        }

        return [
            'user_id' => ['nullable','integer','exists:users,id'],
            'first_name' => ['sometimes','required','string','max:100'],
            'last_name' => ['sometimes','required','string','max:100'],
            'display_name' => ['sometimes','required','string','max:150'],
            'date_of_birth' => ['nullable','date','before:today'],
            'nationality' => ['nullable','string','max:100'],
            'photo' => ['nullable','image','mimes:jpg,jpeg,png,webp','max:4096'],
            'primary_role' => ['sometimes','required', Rule::in([
                'Batter','Bowler','All-rounder','Wicketkeeper','Wicketkeeper-Batter'
            ])],
            'batting_style' => ['nullable', Rule::in(['Right-handed','Left-handed'])],
            'bowling_style' => ['nullable','string','max:80'],
            'fitness_status' => ['sometimes','required', Rule::in(['Fit','Under Observation','Rehabilitation','Unfit'])],
            'status' => ['sometimes','required', Rule::in(['Active','Unavailable','Injured','Suspended','Retired'])],
            'notes' => ['nullable','string','max:5000'],
            'positions' => ['nullable','array'],
            'positions.*' => ['string','max:80'],
        ];
    }
}
