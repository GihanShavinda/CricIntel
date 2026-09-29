<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ScoutingReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'scouting_profile_id',
        'scout_id',
        'competition',
        'report_date',
        'observed_role',
        'strengths',
        'weaknesses',
        'potential',
        'overall_recommendation',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'report_date' => 'date',
            'potential' => 'integer',
        ];
    }

    public function profile(): BelongsTo { return $this->belongsTo(ScoutingProfile::class, 'scouting_profile_id'); }
    public function scout(): BelongsTo { return $this->belongsTo(User::class, 'scout_id'); }
    public function rating(): HasOne { return $this->hasOne(ScoutingRating::class); }
    public function media(): HasMany { return $this->hasMany(ScoutingMedia::class); }
    public function scoutingNotes(): HasMany { return $this->hasMany(ScoutingNote::class); }
}
