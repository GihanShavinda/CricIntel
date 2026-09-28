<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Over extends Model {
    use HasFactory;
    protected $fillable=['innings_id','over_number','bowler_id','legal_balls','runs','wickets','status'];
    protected function casts(): array { return ['over_number'=>'integer','legal_balls'=>'integer','runs'=>'integer','wickets'=>'integer']; }
    public function innings(): BelongsTo { return $this->belongsTo(Innings::class); }
    public function bowler(): BelongsTo { return $this->belongsTo(Player::class,'bowler_id'); }
    public function deliveries(): HasMany { return $this->hasMany(Delivery::class); }
}
