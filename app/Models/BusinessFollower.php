<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusinessFollower extends Model
{
    protected $table = 'business_followers';
    protected $fillable = [
        'business_id',
        'user_id',
        'notifications_enabled',
        'followed_at',
    ];

    protected function casts(): array
    {
        return [
            'notifications_enabled' => 'boolean',
            'followed_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
