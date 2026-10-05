<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StrategyComment extends Model
{
    use HasFactory;

    protected $table = 'comments';

    protected $fillable = [
        'tactical_note_id',
        'author_id',
        'body',
        'is_resolution',
    ];

    protected function casts(): array
    {
        return [
            'is_resolution' => 'boolean',
        ];
    }

    public function note(): BelongsTo
    {
        return $this->belongsTo(TacticalNote::class, 'tactical_note_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
