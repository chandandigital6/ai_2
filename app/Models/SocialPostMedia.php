<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SocialPostMedia extends Model
{
    protected $table = 'social_post_media';
    protected $fillable = [
        'social_post_id',
        'media_type',
        'file_url',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function social_post(): BelongsTo
    {
        return $this->belongsTo(SocialPost::class, 'social_post_id');
    }
}
