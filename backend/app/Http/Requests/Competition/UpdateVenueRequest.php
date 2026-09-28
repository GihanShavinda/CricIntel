<?php
namespace App\Http\Requests\Competition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class UpdateVenueRequest extends FormRequest {
 public function authorize(): bool { return $this->user()?->can('update',$this->route('venue')) ?? false; }
 public function rules(): array { return [
  'name'=>['sometimes','required','string','max:255'],'city'=>['nullable','string','max:100'],'country'=>['nullable','string','max:100'],
  'capacity'=>['nullable','integer','min:0'],'pitch_type'=>['nullable','string','max:100'],
  'boundary_dimensions'=>['nullable','array'],'notes'=>['nullable','string','max:5000'],
  'status'=>['sometimes','required',Rule::in(['active','inactive'])],
 ]; }
}
