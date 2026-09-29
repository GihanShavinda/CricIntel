<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    protected $table = 'attendance';

    protected $fillable = [
        'training_session_player_id','status','arrival_time','notes','marked_by','marked_at',
    ];

    protected function casts(): array
    {
        return ['marked_at'=>'datetime'];
    }

    public function sessionPlayer(): BelongsTo
    {
        return $this->belongsTo(TrainingSessionPlayer::class,'training_session_player_id');
    }

    public function markedBy(): BelongsTo { return $this->belongsTo(User::class,'marked_by'); }
}
