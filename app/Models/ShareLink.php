<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShareLink extends Model
{
    protected $table = 'share_links';
    protected $fillable = [
        'shareable_type',
        'shareable_id',
        'code',
        'source',
        'clicks_count',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'shareable_id' => 'integer',
            'clicks_count' => 'integer',
            'expires_at' => 'datetime',
        ];
    }
}
