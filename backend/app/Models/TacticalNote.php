<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TacticalNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'strategy_plan_id',
        'strategy_section_id',
        'author_id',
        'resolved_by',
        'title',
        'body',
        'status',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(StrategyPlan::class, 'strategy_plan_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(StrategySection::class, 'strategy_section_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(StrategyComment::class, 'tactical_note_id')
            ->orderBy('created_at');
    }
}
