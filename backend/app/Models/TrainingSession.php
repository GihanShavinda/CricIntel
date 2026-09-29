<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id','team_id','coach_id','session_date','start_time',
        'location','duration_minutes','session_type','status','notes',
    ];

    protected function casts(): array
    {
        return ['session_date'=>'date','duration_minutes'=>'integer'];
    }

    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function team(): BelongsTo { return $this->belongsTo(Team::class); }
    public function coach(): BelongsTo { return $this->belongsTo(User::class,'coach_id'); }

    public function drills(): BelongsToMany
    {
        return $this->belongsToMany(TrainingDrill::class,'training_session_drill')
            ->withPivot(['sequence','planned_duration_minutes','notes'])
            ->withTimestamps()
            ->orderBy('training_session_drill.sequence');
    }

    public function sessionPlayers(): HasMany { return $this->hasMany(TrainingSessionPlayer::class); }
    public function fitnessTests(): HasMany { return $this->hasMany(FitnessTest::class); }
    public function assessments(): HasMany { return $this->hasMany(PlayerAssessment::class); }
}
