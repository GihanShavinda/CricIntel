<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StrategyPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'match_id',
        'opponent_team_id',
        'venue_id',
        'created_by',
        'updated_by',
        'title',
        'status',
        'summary',
        'locked_at',
        'locked_by',
    ];

    protected function casts(): array
    {
        return [
            'locked_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(CricketMatch::class, 'match_id');
    }

    public function opponentTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'opponent_team_id');
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function locker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(StrategySection::class)->orderBy('sort_order');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(TacticalNote::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(StrategyAssignment::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(StrategyAttachment::class);
    }

    public function mentions(): HasMany
    {
        return $this->hasMany(Mention::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(StrategyVersion::class)
            ->orderByDesc('version_number');
    }
}
