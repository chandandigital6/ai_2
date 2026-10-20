<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $table = 'products';
    protected $fillable = [
        'business_id',
        'category_id',
        'name',
        'slug',
        'sku',
        'description',
        'price',
        'offer_price',
        'stock_status',
        'stock_quantity',
        'featured_image',
        'video',
        'views_count',
        'orders_count',
        'status',
        'is_featured',
        'is_trending',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'qr_code',
        'specifications',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'offer_price' => 'decimal:2',
            'stock_quantity' => 'integer',
            'views_count' => 'integer',
            'orders_count' => 'integer',

            'is_featured' => 'boolean',
            'is_trending' => 'boolean',
            'specifications' => 'array',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function product_images(): HasMany
    {
        return $this->hasMany(ProductImage::class, 'product_id');
    }

    public function product_variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class, 'product_id');
    }

    public function product_tag(): HasMany
    {
        return $this->hasMany(ProductTag::class, 'product_id');
    }

    public function product_attribute_values(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class, 'product_id');
    }

    public function offer_products(): HasMany
    {
        return $this->hasMany(OfferProduct::class, 'product_id');
    }

    public function product_favourites(): HasMany
    {
        return $this->hasMany(ProductFavourite::class, 'product_id');
    }

    public function cart_items(): HasMany
    {
        return $this->hasMany(CartItem::class, 'product_id');
    }

    public function order_items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'product_id');
    }

    public function business_enquiries(): HasMany
    {
        return $this->hasMany(BusinessEnquiry::class, 'product_id');
    }

    public function activity_events(): HasMany
    {
        return $this->hasMany(ActivityEvent::class, 'product_id');
    }




    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }



    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }



    public function tags()
    {
        return $this->belongsToMany(
            \App\Models\Tag::class,
            'product_tag',
            'product_id',
            'tag_id'
        )->withTimestamps();
    }

    public function attributeValues()
    {
        return $this->belongsToMany(
            \App\Models\AttributeValue::class,
            'product_attribute_values',
            'product_id',
            'attribute_value_id'
        )->withTimestamps();
    }





    public function stockMovements()
    {
        return $this->hasMany(
            \App\Models\ProductStockMovement::class
        );
    }
}
