<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StrategyVersion extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'strategy_plan_id',
        'actor_id',
        'version_number',
        'event_type',
        'entity_type',
        'entity_id',
        'change_summary',
        'snapshot',
        'changes',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
            'changes' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(StrategyPlan::class, 'strategy_plan_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
