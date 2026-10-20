<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Banner extends Model
{
    protected $table = 'banners';
    protected $fillable = [
        'placement',
        'city_id',
        'media',
        'cta_text',
        'cta_url',
        'starts_at',
        'ends_at',
        'sort_order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id');
    }
}
