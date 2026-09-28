<?php
namespace App\Http\Requests\Match;
use Illuminate\Foundation\Http\FormRequest;
class CompleteMatchRequest extends FormRequest {
 public function authorize(): bool { return $this->user()?->can('update',$this->route('match')) ?? false; }
 public function rules(): array { return [
  'winner_team_id'=>['nullable','integer','exists:teams,id'],
  'result_type'=>['nullable','string','max:60'],
  'player_of_match_id'=>['nullable','integer','exists:players,id'],
 ]; }
}
