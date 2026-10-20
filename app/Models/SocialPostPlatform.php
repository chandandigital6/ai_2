<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialPostPlatform extends Model
{
    protected $table = 'social_post_platforms';

    protected $fillable = [
        'container_id',
        'social_post_id',
        'social_account_id',
        'external_post_id',
        'external_post_url',
        'status',
        'error',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function social_post(): BelongsTo
    {
        return $this->belongsTo(SocialPost::class, 'social_post_id');
    }

    public function social_account(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class, 'social_account_id');
    }
}
