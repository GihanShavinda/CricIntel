<?php
namespace App\Http\Requests\Competition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class UpdateTournamentRequest extends FormRequest {
 public function authorize(): bool { return $this->user()?->can('update',$this->route('tournament')) ?? false; }
 public function rules(): array { return [
  'season_id'=>['sometimes','required','integer','exists:seasons,id'],
  'competition_format_id'=>['nullable','integer','exists:competition_formats,id'],
  'name'=>['sometimes','required','string','max:255'],'format'=>['sometimes','required',Rule::in(['T20','ODI','Test','T10','Custom'])],
  'start_date'=>['sometimes','required','date'],'end_date'=>['sometimes','required','date'],
  'status'=>['sometimes','required',Rule::in(['Scheduled','In Progress','Completed','Cancelled'])],
  'organizer'=>['nullable','string','max:255'],'rules_json'=>['nullable','array'],
 ]; }
 public function withValidator($validator): void {
  $validator->after(function($validator){
   $t=$this->route('tournament');
   $start=$this->input('start_date',$t->start_date?->toDateString());
   $end=$this->input('end_date',$t->end_date?->toDateString());
   if($start && $end && strtotime($end)<strtotime($start)) $validator->errors()->add('end_date','The end date must be after or equal to the start date.');
  });
 }
}
