<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class PlayerAvailability extends Model {
    use HasFactory;
    protected $table='player_availability';
    protected $fillable=['player_id','available_from','available_to','reason','status'];
    protected function casts(): array { return ['available_from'=>'date','available_to'=>'date']; }
    public function player(): BelongsTo { return $this->belongsTo(Player::class); }
}
