<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Fixture extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id','tournament_id','home_team_id','away_team_id','venue_id',
        'scheduled_at','match_number','round','status','notes',
    ];

    protected function casts(): array
    {
        return ['scheduled_at'=>'datetime','match_number'=>'integer'];
    }

    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function tournament(): BelongsTo { return $this->belongsTo(Tournament::class); }
    public function homeTeam(): BelongsTo { return $this->belongsTo(Team::class,'home_team_id'); }
    public function awayTeam(): BelongsTo { return $this->belongsTo(Team::class,'away_team_id'); }
    public function venue(): BelongsTo { return $this->belongsTo(Venue::class); }
    public function match(): HasOne { return $this->hasOne(CricketMatch::class); }
}
