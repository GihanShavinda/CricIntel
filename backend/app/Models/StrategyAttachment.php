<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

class StrategyAttachment extends Model
{
    use HasFactory;

    protected $table = 'attachments';

    protected $fillable = [
        'strategy_plan_id',
        'uploaded_by',
        'source_type',
        'source_id',
        'attachment_type',
        'entity_id',
        'label',
        'disk',
        'path',
        'original_filename',
        'mime_type',
        'size_bytes',
        'external_url',
    ];

    protected $appends = [
        'file_url',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'entity_id' => 'integer',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(StrategyPlan::class, 'strategy_plan_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getFileUrlAttribute(): ?string
    {
        if (! $this->disk || ! $this->path) {
            return null;
        }

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk($this->disk);

        return $disk->url($this->path);
    }
}
