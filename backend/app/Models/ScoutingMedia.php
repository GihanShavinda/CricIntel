<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ScoutingMedia extends Model
{
    use HasFactory;

    protected $fillable = [
        'scouting_report_id',
        'uploaded_by',
        'media_type',
        'video_url',
        'disk',
        'path',
        'original_filename',
        'mime_type',
        'size_bytes',
        'caption',
    ];

    protected $appends = ['file_url'];

    protected function casts(): array
    {
        return ['size_bytes' => 'integer'];
    }

    public function report(): BelongsTo { return $this->belongsTo(ScoutingReport::class, 'scouting_report_id'); }
    public function uploader(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by'); }

    public function getFileUrlAttribute(): ?string
    {
        if (! $this->disk || ! $this->path) {
            return null;
        }

        return Storage::disk($this->disk)->url($this->path);
    }
}
