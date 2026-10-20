<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttributeValue extends Model
{
    protected $table = 'attribute_values';
    protected $fillable = [
        'attribute_id',
        'value',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }




public function variants()
{
    return $this->belongsToMany(
        \App\Models\ProductVariant::class,
        'product_variant_attribute_values',
        'attribute_value_id',
        'product_variant_id'
    )->withTimestamps();
}


public function products()
{
    return $this->belongsToMany(
        \App\Models\Product::class,
        'product_attribute_values',
        'attribute_value_id',
        'product_id'
    )->withTimestamps();
}

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class, 'attribute_id');
    }

    public function product_attribute_values(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class, 'attribute_value_id');
    }





}
