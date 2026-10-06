<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NlAnalyticsQuery extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'user_id',
        'natural_language_query',
        'intent',
        'controlled_query',
        'resolved_filters',
        'structured_result',
        'explanation',
        'visualization',
        'status',
        'errors',
        'executed_at',
    ];

    protected function casts(): array
    {
        return [
            'controlled_query' => 'array',
            'resolved_filters' => 'array',
            'structured_result' => 'array',
            'visualization' => 'array',
            'errors' => 'array',
            'executed_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
