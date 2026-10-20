<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Review extends Model
{
    protected $appends = ['photos'];

    public function getPhotosAttribute(): array
    {
        return $this->review_images->map(fn ($photo) => ['id' => $photo->id, 'url' => url('/review-photos/'.$photo->id), 'api_url' => url('/api/v1/review-photos/'.$photo->id)])->all();
    }

    protected $table = 'reviews';

    protected $fillable = [
        'business_id',
        'user_id',
        'order_id',
        'rating',
        'review',
        'status',
        'business_reply',
        'replied_at',
        'helpful_count',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'replied_at' => 'datetime',
            'helpful_count' => 'integer',
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

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function review_images(): HasMany
    {
        return $this->hasMany(ReviewImage::class, 'review_id');
    }
}
