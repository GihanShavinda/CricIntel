<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'short_name',
        'logo',
        'country',
        'timezone',
        'description',
        'status',
        'created_by',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function clubs(): HasMany
    {
        return $this->hasMany(Club::class);
    }
    public function seasons(): HasMany
    {
        return $this->hasMany(Season::class);
    }
    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }
    public function venues(): HasMany
    {
        return $this->hasMany(Venue::class);
    }
    public function tournaments(): HasMany
    {
        return $this->hasMany(Tournament::class);
    }
    public function fixtures(): HasMany
    {
        return $this->hasMany(Fixture::class);
    }
    public function matches(): HasMany
    {
        return $this->hasMany(CricketMatch::class);
    }
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organization_user')->withPivot(['title', 'status'])->withTimestamps();
    }
}
