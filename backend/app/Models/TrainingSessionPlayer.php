<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TrainingSessionPlayer extends Model
{
    use HasFactory;

    protected $fillable = ['training_session_id','player_id','notes'];

    public function trainingSession(): BelongsTo { return $this->belongsTo(TrainingSession::class); }
    public function player(): BelongsTo { return $this->belongsTo(Player::class); }
    public function attendance(): HasOne { return $this->hasOne(Attendance::class); }
}
