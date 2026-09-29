<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScoutingNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'scouting_profile_id',
        'scouting_report_id',
        'author_id',
        'note',
        'is_private',
    ];

    protected function casts(): array
    {
        return ['is_private' => 'boolean'];
    }

    public function profile(): BelongsTo { return $this->belongsTo(ScoutingProfile::class, 'scouting_profile_id'); }
    public function report(): BelongsTo { return $this->belongsTo(ScoutingReport::class, 'scouting_report_id'); }
    public function author(): BelongsTo { return $this->belongsTo(User::class, 'author_id'); }
}
