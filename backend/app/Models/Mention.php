<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mention extends Model
{
    use HasFactory;

    protected $fillable = [
        'strategy_plan_id',
        'mentioned_user_id',
        'mentioned_by',
        'source_type',
        'source_id',
        'token',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(StrategyPlan::class, 'strategy_plan_id');
    }

    public function mentionedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentioned_user_id');
    }

    public function mentioner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentioned_by');
    }
}
