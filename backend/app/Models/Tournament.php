<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tournament extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'season_id',
        'competition_format_id',
        'name',
        'format',
        'start_date',
        'end_date',
        'status',
        'organizer',
        'rules_json',
    ];

    protected function casts(): array
    {
        return [
            'start_date' =>
                'date',

            'end_date' =>
                'date',

            'rules_json' =>
                'array',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Organization
    |--------------------------------------------------------------------------
    */

    public function organization(): BelongsTo
    {
        return $this->belongsTo(
            Organization::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Season
    |--------------------------------------------------------------------------
    */

    public function season(): BelongsTo
    {
        return $this->belongsTo(
            Season::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Competition Format
    |--------------------------------------------------------------------------
    */

    public function competitionFormat(): BelongsTo
    {
        return $this->belongsTo(
            CompetitionFormat::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Registered Teams
    |--------------------------------------------------------------------------
    */

    public function teams(): BelongsToMany
    {
        return $this
            ->belongsToMany(
                Team::class,
                'tournament_team'
            )
            ->withPivot([
                'seed',
                'status',
            ])
            ->withTimestamps();
    }

    /*
    |--------------------------------------------------------------------------
    | Fixtures
    |--------------------------------------------------------------------------
    */

    public function fixtures(): HasMany
    {
        return $this->hasMany(
            Fixture::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | P9 - Tournament Squads
    |--------------------------------------------------------------------------
    |
    | Each participating team can maintain its tournament-level squad.
    |
    */

    public function squads(): HasMany
    {
        return $this->hasMany(
            Squad::class,
            'tournament_id'
        );
    }
}
