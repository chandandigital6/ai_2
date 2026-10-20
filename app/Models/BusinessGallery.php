<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class BusinessGallery extends Model
{
    protected $table = 'business_gallery';

    protected $fillable = [
        'business_id',
        'media_type',
        'file_url',     // DB column: storage path ya external URL
        'thumbnail',
        'title',
        'description',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    /**
     * Browser ke liye full URL.
     * `file_url` column ka raw path -> public URL.
     */
    public function getMediaUrlAttribute(): ?string
    {
        return $this->resolveUrl($this->file_url);
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->resolveUrl($this->thumbnail);
    }

    public function getIsVideoAttribute(): bool
    {
        if ($this->media_type === 'video') {
            return true;
        }

        return in_array(
            strtolower(pathinfo((string) $this->file_url, PATHINFO_EXTENSION)),
            ['mp4', 'webm', 'mov', 'ogg'],
            true
        );
    }

    private function resolveUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (preg_match('~^https?://~i', $path)) {
            return $path;
        }

        return asset('storage/' . ltrim($path, '/'));
    }
}
