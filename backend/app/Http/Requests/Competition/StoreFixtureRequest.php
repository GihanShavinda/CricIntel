<?php
namespace App\Http\Requests\Competition;
use App\Models\Fixture;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class StoreFixtureRequest extends FormRequest {
 public function authorize(): bool { return $this->user()?->can('create',[Fixture::class,$this->route('organization')]) ?? false; }
 public function rules(): array { return [
  'tournament_id'=>['required','integer','exists:tournaments,id'],
  'home_team_id'=>['required','integer','different:away_team_id','exists:teams,id'],
  'away_team_id'=>['required','integer','exists:teams,id'],
  'venue_id'=>['nullable','integer','exists:venues,id'],
  'scheduled_at'=>['required','date'],'match_number'=>['nullable','integer','min:1'],
  'round'=>['nullable','string','max:100'],
  'status'=>['required',Rule::in(['Scheduled','Delayed','In Progress','Completed','Abandoned','Cancelled'])],
  'notes'=>['nullable','string','max:5000'],
 ]; }
}
