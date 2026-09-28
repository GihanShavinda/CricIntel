<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Wicket extends Model {
    use HasFactory;
    protected $fillable=['delivery_id','innings_id','dismissed_player_id','wicket_type','bowler_id','fielder_id','runs_at_wicket','wicket_number'];
    protected function casts(): array { return ['runs_at_wicket'=>'integer','wicket_number'=>'integer']; }
    public function delivery(): BelongsTo { return $this->belongsTo(Delivery::class); }
    public function innings(): BelongsTo { return $this->belongsTo(Innings::class); }
}
