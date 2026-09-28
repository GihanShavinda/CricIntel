<?php
namespace App\Http\Requests\Competition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class UpdateFixtureRequest extends FormRequest {
 public function authorize(): bool { return $this->user()?->can('update',$this->route('fixture')) ?? false; }
 public function rules(): array { return [
  'tournament_id'=>['sometimes','required','integer','exists:tournaments,id'],
  'home_team_id'=>['sometimes','required','integer','different:away_team_id','exists:teams,id'],
  'away_team_id'=>['sometimes','required','integer','exists:teams,id'],
  'venue_id'=>['nullable','integer','exists:venues,id'],'scheduled_at'=>['sometimes','required','date'],
  'match_number'=>['nullable','integer','min:1'],'round'=>['nullable','string','max:100'],
  'status'=>['sometimes','required',Rule::in(['Scheduled','Delayed','In Progress','Completed','Abandoned','Cancelled'])],
  'notes'=>['nullable','string','max:5000'],
 ]; }
}
