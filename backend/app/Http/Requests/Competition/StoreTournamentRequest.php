<?php
namespace App\Http\Requests\Competition;
use App\Models\Tournament;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class StoreTournamentRequest extends FormRequest {
 public function authorize(): bool { return $this->user()?->can('create',[Tournament::class,$this->route('organization')]) ?? false; }
 public function rules(): array { return [
  'season_id'=>['required','integer','exists:seasons,id'],
  'competition_format_id'=>['nullable','integer','exists:competition_formats,id'],
  'name'=>['required','string','max:255'],'format'=>['required',Rule::in(['T20','ODI','Test','T10','Custom'])],
  'start_date'=>['required','date'],'end_date'=>['required','date','after_or_equal:start_date'],
  'status'=>['required',Rule::in(['Scheduled','In Progress','Completed','Cancelled'])],
  'organizer'=>['nullable','string','max:255'],'rules_json'=>['nullable','array'],
 ]; }
}
