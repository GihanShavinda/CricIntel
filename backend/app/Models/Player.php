<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Player extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'user_id',
        'first_name',
        'last_name',
        'display_name',
        'date_of_birth',
        'nationality',
        'photo',
        'primary_role',
        'batting_style',
        'bowling_style',
        'fitness_status',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' =>
                'date',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Core Relationships
    |--------------------------------------------------------------------------
    */

    public function organization(): BelongsTo
    {
        return $this->belongsTo(
            Organization::class
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | P2 / P3 - Team Membership
    |--------------------------------------------------------------------------
    */

    public function teams(): BelongsToMany
    {
        return $this
            ->belongsToMany(
                Team::class,
                'player_team'
            )
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
    | P3 - Player Profile Relationships
    |--------------------------------------------------------------------------
    */

    public function positions(): HasMany
    {
        return $this->hasMany(
            PlayerPosition::class
        );
    }

    public function availability(): HasMany
    {
        return $this->hasMany(
            PlayerAvailability::class
        );
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(
            PlayerContact::class
        );
    }

    public function documents(): HasMany
    {
        return $this->hasMany(
            PlayerDocument::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | P9 - Tournament Squad Memberships
    |--------------------------------------------------------------------------
    */

    public function squadMemberships(): HasMany
    {
        return $this->hasMany(
            SquadPlayer::class,
            'player_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | P9 - Match Squad Memberships
    |--------------------------------------------------------------------------
    */

    public function matchSquadMemberships(): HasMany
    {
        return $this->hasMany(
            MatchSquadPlayer::class,
            'player_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | P9 - Playing XI
    |--------------------------------------------------------------------------
    */

    public function playingXiEntries(): HasMany
    {
        return $this->hasMany(
            PlayingXi::class,
            'player_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | P9 - Batting Orders
    |--------------------------------------------------------------------------
    */

    public function battingOrderEntries(): HasMany
    {
        return $this->hasMany(
            BattingOrder::class,
            'player_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | P9 - Bowling Assignments
    |--------------------------------------------------------------------------
    */

    public function bowlingAssignments(): HasMany
    {
        return $this->hasMany(
            BowlingAssignment::class,
            'player_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | P9 - Selection Decisions
    |--------------------------------------------------------------------------
    */

    public function selectionDecisions(): HasMany
    {
        return $this->hasMany(
            SelectionDecision::class,
            'player_id'
        );
    }
}
