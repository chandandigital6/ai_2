<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Offer extends Model
{
    use SoftDeletes;

    protected $table = 'offers';
    protected $fillable = [
        'business_id',
        'title',
        'description',
        'offer_type',
        'discount_value',
        'starts_at',
        'ends_at',
        'banner',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'starts_at' => 'date',
            'ends_at' => 'date',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function offer_products(): HasMany
    {
        return $this->hasMany(OfferProduct::class, 'offer_id');
    }

    public function offer_services(): HasMany
    {
        return $this->hasMany(OfferService::class, 'offer_id');
    }

    public function activity_events(): HasMany
    {
        return $this->hasMany(ActivityEvent::class, 'offer_id');
    }
}
