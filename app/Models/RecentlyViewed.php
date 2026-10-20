<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecentlyViewed extends Model
{
    protected $table = 'recently_viewed';
    protected $fillable = [
        'user_id',
        'viewable_type',
        'viewable_id',
        'viewed_at',
    ];

    protected function casts(): array
    {
        return [
            'viewable_id' => 'integer',
            'viewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
