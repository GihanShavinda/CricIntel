<?php

namespace App\Http\Requests\Season;

use App\Models\Club;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncSeasonTeamsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('season')) ?? false;
    }

    public function rules(): array
    {
        $clubIds = Club::query()
            ->where('organization_id', $this->route('organization')->id)
            ->pluck('id');

        return [
            'team_ids' => ['required','array'],
            'team_ids.*' => [
                'integer',
                Rule::exists('teams','id')->whereIn('club_id', $clubIds),
            ],
        ];
    }
}
