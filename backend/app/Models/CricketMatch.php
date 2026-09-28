<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
class CricketMatch extends Model {
    use HasFactory;
    protected $table='matches';
    protected $fillable=[
        'organization_id','fixture_id','toss_winner_id','toss_decision','status',
        'result_type','winner_team_id','player_of_match_id','target_runs','max_overs'
    ];
    protected function casts(): array { return ['target_runs'=>'integer','max_overs'=>'integer']; }
    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function fixture(): BelongsTo { return $this->belongsTo(Fixture::class); }
    public function tossWinner(): BelongsTo { return $this->belongsTo(Team::class,'toss_winner_id'); }
    public function winnerTeam(): BelongsTo { return $this->belongsTo(Team::class,'winner_team_id'); }
    public function playerOfMatch(): BelongsTo { return $this->belongsTo(Player::class,'player_of_match_id'); }
    public function innings(): HasMany { return $this->hasMany(Innings::class,'match_id')->orderBy('innings_number'); }
    public function players(): BelongsToMany { return $this->belongsToMany(Player::class,'match_players','match_id','player_id')->withPivot(['team_id','role','playing_xi'])->withTimestamps(); }
}
