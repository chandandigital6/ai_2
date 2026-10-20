<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use SoftDeletes;

    protected $table = 'services';

    protected $fillable = [
        'business_id',
        'category_id',
        'name',
        'slug',
        'price',
        'offer_price',
        'price_type',
        'duration_minutes',
        'description',
        'status',
        'featured_image',
        'views_count',
        'enquiries_count',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'offer_price' => 'decimal:2',
            'duration_minutes' => 'integer',
            'views_count' => 'integer',
            'enquiries_count' => 'integer',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(
            Business::class,
            'business_id'
        );
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(
            Category::class,
            'category_id'
        );
    }

    // Used by current Services CRUD and Gallery.
    public function images(): HasMany
    {
        return $this->hasMany(
            ServiceImage::class,
            'service_id'
        )->orderBy('sort_order')->orderBy('id');
    }

    // Keep existing relationship for compatibility.
    public function service_images(): HasMany
    {
        return $this->hasMany(
            ServiceImage::class,
            'service_id'
        );
    }

    public function offer_services(): HasMany
    {
        return $this->hasMany(
            OfferService::class,
            'service_id'
        );
    }

    public function business_enquiries(): HasMany
    {
        return $this->hasMany(
            BusinessEnquiry::class,
            'service_id'
        );
    }

    public function service_enquiries(): HasMany
    {
        return $this->hasMany(
            ServiceEnquiry::class,
            'service_id'
        );
    }

    public function activity_events(): HasMany
    {
        return $this->hasMany(
            ActivityEvent::class,
            'service_id'
        );
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(
            Appointment::class,
            'service_id'
        );
    }
}
