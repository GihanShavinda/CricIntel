<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
class Delivery extends Model {
    use HasFactory;
    protected $fillable=[
        'innings_id','over_id','sequence_number','ball_number','bowler_id','batter_id','non_striker_id',
        'runs_off_bat','extra_runs','total_runs','extra_type','is_legal','is_free_hit',
        'wicket','wicket_type','dismissed_player_id','fielder_id',
        'shot_type','delivery_type','pitch_zone','ball_speed','delivery_timestamp'
    ];
    protected function casts(): array {
        return ['sequence_number'=>'integer','ball_number'=>'integer','runs_off_bat'=>'integer','extra_runs'=>'integer',
            'total_runs'=>'integer','is_legal'=>'boolean','is_free_hit'=>'boolean','wicket'=>'boolean',
            'ball_speed'=>'decimal:2','delivery_timestamp'=>'datetime'];
    }
    public function innings(): BelongsTo { return $this->belongsTo(Innings::class); }
    public function over(): BelongsTo { return $this->belongsTo(Over::class); }
    public function bowler(): BelongsTo { return $this->belongsTo(Player::class,'bowler_id'); }
    public function batter(): BelongsTo { return $this->belongsTo(Player::class,'batter_id'); }
    public function nonStriker(): BelongsTo { return $this->belongsTo(Player::class,'non_striker_id'); }
    public function dismissedPlayer(): BelongsTo { return $this->belongsTo(Player::class,'dismissed_player_id'); }
    public function fielder(): BelongsTo { return $this->belongsTo(Player::class,'fielder_id'); }
    public function wicketRecord(): HasOne { return $this->hasOne(Wicket::class); }
}
