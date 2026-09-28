<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Innings extends Model {
    use HasFactory;
    protected $fillable=[
        'match_id','batting_team_id','bowling_team_id','innings_number','runs','wickets',
        'legal_balls','overs_completed','status','striker_id','non_striker_id','current_bowler_id',
        'free_hit_next','target'
    ];
    protected function casts(): array {
        return ['runs'=>'integer','wickets'=>'integer','legal_balls'=>'integer','overs_completed'=>'decimal:1','free_hit_next'=>'boolean','target'=>'integer'];
    }
    public function match(): BelongsTo { return $this->belongsTo(CricketMatch::class,'match_id'); }
    public function battingTeam(): BelongsTo { return $this->belongsTo(Team::class,'batting_team_id'); }
    public function bowlingTeam(): BelongsTo { return $this->belongsTo(Team::class,'bowling_team_id'); }
    public function striker(): BelongsTo { return $this->belongsTo(Player::class,'striker_id'); }
    public function nonStriker(): BelongsTo { return $this->belongsTo(Player::class,'non_striker_id'); }
    public function currentBowler(): BelongsTo { return $this->belongsTo(Player::class,'current_bowler_id'); }
    public function overs(): HasMany { return $this->hasMany(Over::class); }
    public function deliveries(): HasMany { return $this->hasMany(Delivery::class); }
}
