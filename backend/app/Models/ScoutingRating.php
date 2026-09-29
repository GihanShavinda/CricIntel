<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScoutingRating extends Model
{
    use HasFactory;

    protected $fillable = [
        'scouting_report_id',
        'technical_rating',
        'tactical_rating',
        'physical_rating',
        'fielding_rating',
        'mental_decision_rating',
        'overall_rating',
    ];

    protected function casts(): array
    {
        return [
            'technical_rating' => 'integer',
            'tactical_rating' => 'integer',
            'physical_rating' => 'integer',
            'fielding_rating' => 'integer',
            'mental_decision_rating' => 'integer',
            'overall_rating' => 'float',
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(ScoutingReport::class, 'scouting_report_id');
    }
}
