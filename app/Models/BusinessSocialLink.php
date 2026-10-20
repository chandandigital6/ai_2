<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessSocialLink extends Model
{
    protected $fillable = [
        'business_id',
        'platform',
        'username',
        'url',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
