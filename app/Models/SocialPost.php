<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SocialPost extends Model
{
    protected $table = 'social_posts';

    protected $fillable = [
        'created_by', 'request_key',
        'business_id',
        'caption',
        'post_type',
        'publish_mode',
        'scheduled_at',
        'is_ai_generated',
        'overall_status',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'is_ai_generated' => 'boolean',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function social_post_media(): HasMany
    {
        return $this->hasMany(SocialPostMedia::class, 'social_post_id');
    }

    public function social_post_platforms(): HasMany
    {
        return $this->hasMany(SocialPostPlatform::class, 'social_post_id');
    }
}
