<?php
namespace App\Http\Requests\Match;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class RecordDeliveryRequest extends FormRequest {
 public function authorize(): bool { return $this->user()?->can('update',$this->route('match')) ?? false; }
 public function rules(): array { return [
  'bowler_id'=>['required','integer','exists:players,id'],
  'batter_id'=>['required','integer','exists:players,id'],
  'non_striker_id'=>['required','integer','different:batter_id','exists:players,id'],
  'runs_off_bat'=>['required','integer','min:0','max:6'],
  'extra_runs'=>['required','integer','min:0','max:10'],
  'extra_type'=>['required',Rule::in(['none','wide','no_ball','bye','leg_bye','penalty'])],
  'wicket'=>['required','boolean'],
  'wicket_type'=>['nullable','string','max:40'],
  'dismissed_player_id'=>['nullable','integer','exists:players,id'],
  'fielder_id'=>['nullable','integer','exists:players,id'],
  'shot_type'=>['nullable','string','max:60'],
  'delivery_type'=>['nullable','string','max:60'],
  'pitch_zone'=>['nullable','string','max:60'],
  'ball_speed'=>['nullable','numeric','min:0','max:250'],
  'timestamp'=>['nullable','date'],
 ]; }
}
