<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReviewImage extends Model
{
    protected $table = 'review_images';
    protected $fillable = [
        'review_id',
        'image',
    ];

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class, 'review_id');
    }
}
