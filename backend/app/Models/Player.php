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
            'date_of_birth' => 'date',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'player_team')
            ->withPivot([
                'jersey_number',
                'joined_at',
                'left_at',
                'is_current',
            ])
            ->withTimestamps();
    }

    public function positions(): HasMany
    {
        return $this->hasMany(PlayerPosition::class);
    }

    public function availability(): HasMany
    {
        return $this->hasMany(PlayerAvailability::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(PlayerContact::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(PlayerDocument::class);
    }

    /*
    |--------------------------------------------------------------------------
    | P9 - Selection Relationships
    |--------------------------------------------------------------------------
    */

    public function squadMemberships(): HasMany
    {
        return $this->hasMany(SquadPlayer::class, 'player_id');
    }

    public function matchSquadMemberships(): HasMany
    {
        return $this->hasMany(MatchSquadPlayer::class, 'player_id');
    }

    public function playingXiEntries(): HasMany
    {
        return $this->hasMany(PlayingXi::class, 'player_id');
    }

    public function battingOrderEntries(): HasMany
    {
        return $this->hasMany(BattingOrder::class, 'player_id');
    }

    public function bowlingAssignments(): HasMany
    {
        return $this->hasMany(BowlingAssignment::class, 'player_id');
    }

    public function selectionDecisions(): HasMany
    {
        return $this->hasMany(SelectionDecision::class, 'player_id');
    }


    /*
    |--------------------------------------------------------------------------
    | P10 - Training & Development
    |--------------------------------------------------------------------------
    */

    public function trainingSessionMemberships(): HasMany
    {
        return $this->hasMany(TrainingSessionPlayer::class, 'player_id');
    }

    public function fitnessTests(): HasMany
    {
        return $this->hasMany(FitnessTest::class, 'player_id');
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(PlayerAssessment::class, 'player_id');
    }

    public function trainingObjectives(): HasMany
    {
        return $this->hasMany(TrainingObjective::class, 'player_id');
    }

    public function developmentPlans(): HasMany
    {
        return $this->hasMany(DevelopmentPlan::class, 'player_id');
    }


    /*
    |--------------------------------------------------------------------------
    | P11 - Scouting & Recruitment
    |--------------------------------------------------------------------------
    */

    public function scoutingProfiles(): HasMany
    {
        return $this->hasMany(ScoutingProfile::class, 'existing_player_id');
    }

    public function convertedFromScoutingProfiles(): HasMany
    {
        return $this->hasMany(ScoutingProfile::class, 'converted_player_id');
    }

}
