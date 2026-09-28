<?php
namespace App\Http\Requests\Match;
use Illuminate\Foundation\Http\FormRequest;
class StartInningsRequest extends FormRequest {
 public function authorize(): bool { return $this->user()?->can('update',$this->route('match')) ?? false; }
 public function rules(): array { return [
  'batting_team_id'=>['required','integer','different:bowling_team_id','exists:teams,id'],
  'bowling_team_id'=>['required','integer','exists:teams,id'],
  'innings_number'=>['required','integer','min:1','max:4'],
  'striker_id'=>['required','integer','different:non_striker_id','exists:players,id'],
  'non_striker_id'=>['required','integer','exists:players,id'],
  'bowler_id'=>['required','integer','exists:players,id'],
 ]; }
}
