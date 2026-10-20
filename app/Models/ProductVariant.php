<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    protected $table = 'product_variants';
    protected $fillable = [
        'product_id',
        'name',
        'sku',
        'price',
        'offer_price',
        'stock_quantity',
        'image',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'offer_price' => 'decimal:2',
            'stock_quantity' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function cart_items(): HasMany
    {
        return $this->hasMany(CartItem::class, 'variant_id');
    }

    public function order_items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'variant_id');
    }

    public function attributeValues()
    {
        return $this->belongsToMany(
            \App\Models\AttributeValue::class,
            'product_variant_attribute_values',
            'product_variant_id',
            'attribute_value_id'
        )->withTimestamps();
    }

    public function stockMovements()
    {
        return $this->hasMany(
            \App\Models\ProductStockMovement::class,
            'product_variant_id'
        );
    }
}
