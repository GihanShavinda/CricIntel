<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class PlayerDocument extends Model {
    use HasFactory;
    protected $fillable=['player_id','type','name','path','expires_at'];
    protected function casts(): array { return ['expires_at'=>'date']; }
    public function player(): BelongsTo { return $this->belongsTo(Player::class); }
}
