<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FitnessTest extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id','player_id','training_session_id','recorded_by',
        'test_type','tested_at','value','unit','measurements','notes',
    ];

    protected function casts(): array
    {
        return ['tested_at'=>'date','value'=>'float','measurements'=>'array'];
    }

    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function player(): BelongsTo { return $this->belongsTo(Player::class); }
    public function trainingSession(): BelongsTo { return $this->belongsTo(TrainingSession::class); }
    public function recordedBy(): BelongsTo { return $this->belongsTo(User::class,'recorded_by'); }
}
