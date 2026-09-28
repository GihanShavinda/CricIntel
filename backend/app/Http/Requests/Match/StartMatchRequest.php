<?php
namespace App\Http\Requests\Match;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class StartMatchRequest extends FormRequest {
 public function authorize(): bool { return $this->user()?->can('update',$this->route('match')) ?? false; }
 public function rules(): array { return [
  'toss_winner_id'=>['nullable','integer','exists:teams,id'],
  'toss_decision'=>['nullable',Rule::in(['bat','bowl'])],
  'max_overs'=>['nullable','integer','min:1','max:500'],
 ]; }
}
