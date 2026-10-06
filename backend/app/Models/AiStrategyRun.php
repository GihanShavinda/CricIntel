<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiStrategyRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'match_id',
        'user_id',
        'question',
        'context_hash',
        'context_snapshot',
        'deterministic_recommendations',
        'provider',
        'model',
        'raw_llm_response',
        'validated_response',
        'validation_status',
        'validation_errors',
        'prompt_tokens',
        'completion_tokens',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'context_snapshot' => 'array',
            'deterministic_recommendations' => 'array',
            'raw_llm_response' => 'array',
            'validated_response' => 'array',
            'validation_errors' => 'array',
            'generated_at' => 'datetime',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
