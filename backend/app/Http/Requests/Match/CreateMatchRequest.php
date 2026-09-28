<?php
namespace App\Http\Requests\Match;
use App\Models\CricketMatch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class CreateMatchRequest extends FormRequest {
 public function authorize(): bool { return $this->user()?->can('create',[CricketMatch::class,$this->route('organization')]) ?? false; }
 public function rules(): array { return [
  'fixture_id'=>['required','integer','exists:fixtures,id'],
  'toss_winner_id'=>['nullable','integer','exists:teams,id'],
  'toss_decision'=>['nullable',Rule::in(['bat','bowl'])],
  'max_overs'=>['nullable','integer','min:1','max:500'],
  'players'=>['nullable','array'],
  'players.*.player_id'=>['required','integer','exists:players,id'],
  'players.*.team_id'=>['required','integer','exists:teams,id'],
  'players.*.role'=>['nullable','string','max:60'],
  'players.*.playing_xi'=>['nullable','boolean'],
 ]; }
}
