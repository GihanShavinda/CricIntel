<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScoutingProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'existing_player_id',
        'converted_player_id',
        'created_by',
        'first_name',
        'last_name',
        'display_name',
        'date_of_birth',
        'nationality',
        'role',
        'batting_style',
        'bowling_style',
        'current_team',
        'current_competition',
        'source',
        'status',
        'summary',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function existingPlayer(): BelongsTo { return $this->belongsTo(Player::class, 'existing_player_id'); }
    public function convertedPlayer(): BelongsTo { return $this->belongsTo(Player::class, 'converted_player_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function reports(): HasMany { return $this->hasMany(ScoutingReport::class); }
    public function notes(): HasMany { return $this->hasMany(ScoutingNote::class); }
}
