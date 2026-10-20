<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductStockMovement extends Model
{
    protected $table = 'product_stock_movements';

    protected $fillable = [
        'business_id',
        'product_id',
        'product_variant_id',
        'user_id',
        'movement_type',
        'quantity_change',
        'quantity_before',
        'quantity_after',
        'reason',
    ];

    protected $casts = [
        'quantity_change' => 'integer',
        'quantity_before' => 'integer',
        'quantity_after' => 'integer',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(
            ProductVariant::class,
            'product_variant_id'
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
