<?php
namespace App\Http\Requests\Player;

use App\Models\Player;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlayerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', [Player::class, $this->route('organization')]) ?? false;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['nullable','integer','exists:users,id'],
            'first_name' => ['required','string','max:100'],
            'last_name' => ['required','string','max:100'],
            'display_name' => ['required','string','max:150'],
            'date_of_birth' => ['nullable','date','before:today'],
            'nationality' => ['nullable','string','max:100'],
            'photo' => ['nullable','image','mimes:jpg,jpeg,png,webp','max:4096'],
            'primary_role' => ['required', Rule::in([
                'Batter','Bowler','All-rounder','Wicketkeeper','Wicketkeeper-Batter'
            ])],
            'batting_style' => ['nullable', Rule::in(['Right-handed','Left-handed'])],
            'bowling_style' => ['nullable','string','max:80'],
            'fitness_status' => ['required', Rule::in(['Fit','Under Observation','Rehabilitation','Unfit'])],
            'status' => ['required', Rule::in(['Active','Unavailable','Injured','Suspended','Retired'])],
            'notes' => ['nullable','string','max:5000'],
            'positions' => ['nullable','array'],
            'positions.*' => ['string','max:80'],
        ];
    }
}
