<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    protected $table = 'orders';

    protected $fillable = [
        'business_id',
        'customer_id',
        'order_number',
        'customer_name',
        'customer_phone',
        'address_snapshot',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'shipping_amount',
        'tax_snapshot',
        'total_amount',
        'payment_method',
        'payment_status',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'address_snapshot' => 'array',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'shipping_amount' => 'decimal:2',
            'tax_snapshot' => 'array',
            'total_amount' => 'decimal:2',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function order_items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'order_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
