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
        'organization_id','user_id','first_name','last_name','display_name',
        'date_of_birth','nationality','photo','primary_role','batting_style',
        'bowling_style','fitness_status','status','notes',
    ];

    protected function casts(): array
    {
        return ['date_of_birth' => 'date'];
    }

    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'player_team')
            ->withPivot(['jersey_number','joined_at','left_at','is_current'])
            ->withTimestamps();
    }

    public function positions(): HasMany { return $this->hasMany(PlayerPosition::class); }
    public function availability(): HasMany { return $this->hasMany(PlayerAvailability::class); }
    public function contacts(): HasMany { return $this->hasMany(PlayerContact::class); }
    public function documents(): HasMany { return $this->hasMany(PlayerDocument::class); }
}
