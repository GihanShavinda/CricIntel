<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StrategySection extends Model
{
    use HasFactory;

    protected $fillable = [
        'strategy_plan_id',
        'updated_by',
        'section_key',
        'title',
        'content',
        'structured_data',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'structured_data' => 'array',
            'sort_order' => 'integer',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(StrategyPlan::class, 'strategy_plan_id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(TacticalNote::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(StrategyAssignment::class);
    }
}
