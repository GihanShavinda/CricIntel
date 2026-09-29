<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'club_id',
        'name',
        'short_name',
        'gender',
        'category',
        'age_group',
        'format_preferences',
        'home_ground',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'format_preferences' => 'array',
        ];
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function seasons(): BelongsToMany
    {
        return $this->belongsToMany(Season::class, 'season_team')
            ->withTimestamps();
    }

    public function players(): BelongsToMany
    {
        return $this->belongsToMany(Player::class, 'player_team')
            ->withPivot([
                'jersey_number',
                'joined_at',
                'left_at',
                'is_current',
            ])
            ->withTimestamps();
    }

    /*
    |--------------------------------------------------------------------------
    | P9 - Selection Relationships
    |--------------------------------------------------------------------------
    */

    public function squads(): HasMany
    {
        return $this->hasMany(Squad::class, 'team_id');
    }

    public function matchSquads(): HasMany
    {
        return $this->hasMany(MatchSquad::class, 'team_id');
    }

    public function selectionDecisions(): HasMany
    {
        return $this->hasMany(SelectionDecision::class, 'team_id');
    }


    /*
    |--------------------------------------------------------------------------
    | P10 - Training Sessions
    |--------------------------------------------------------------------------
    */

    public function trainingSessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class, 'team_id');
    }

}
