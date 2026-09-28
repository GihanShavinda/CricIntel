<?php
namespace App\Http\Requests\Competition;
use Illuminate\Foundation\Http\FormRequest;
class SyncTournamentTeamsRequest extends FormRequest {
 public function authorize(): bool { return $this->user()?->can('update',$this->route('tournament')) ?? false; }
 public function rules(): array { return [
  'teams'=>['required','array'],
  'teams.*.team_id'=>['required','integer','exists:teams,id'],
  'teams.*.seed'=>['nullable','integer','min:1'],
  'teams.*.status'=>['required','in:registered,withdrawn,eliminated'],
 ]; }
}
