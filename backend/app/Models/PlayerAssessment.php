<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerAssessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id','player_id','training_session_id','coach_id','assessed_at',
        'technical_rating','tactical_rating','fitness_rating','attitude_rating',
        'strengths','weaknesses','notes',
    ];

    protected function casts(): array
    {
        return [
            'assessed_at'=>'date','technical_rating'=>'integer','tactical_rating'=>'integer',
            'fitness_rating'=>'integer','attitude_rating'=>'integer',
        ];
    }

    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function player(): BelongsTo { return $this->belongsTo(Player::class); }
    public function trainingSession(): BelongsTo { return $this->belongsTo(TrainingSession::class); }
    public function coach(): BelongsTo { return $this->belongsTo(User::class,'coach_id'); }
}
