<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'event_type',
        'database_enabled',
        'email_enabled',
        'realtime_enabled',
    ];

    protected function casts(): array
    {
        return [
            'database_enabled' => 'boolean',
            'email_enabled' => 'boolean',
            'realtime_enabled' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
