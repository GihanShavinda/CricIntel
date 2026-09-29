<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TrainingDrill extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id','name','category','objective','duration_minutes',
        'difficulty','notes','is_active','created_by',
    ];

    protected function casts(): array
    {
        return ['duration_minutes'=>'integer','is_active'=>'boolean'];
    }

    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class,'created_by'); }

    public function sessions(): BelongsToMany
    {
        return $this->belongsToMany(TrainingSession::class,'training_session_drill')
            ->withPivot(['sequence','planned_duration_minutes','notes'])
            ->withTimestamps();
    }
}
