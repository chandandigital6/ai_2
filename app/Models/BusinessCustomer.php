<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusinessCustomer extends Model
{
    protected $table = 'business_customers';
    protected $fillable = [
        'business_id',
        'user_id',
        'name',
        'phone',
        'email',
        'source',
        'orders_count',
        'total_order_value',
        'last_order_at',
        'last_contact_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'orders_count' => 'integer',
            'total_order_value' => 'decimal:2',
            'last_order_at' => 'datetime',
            'last_contact_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function customer_notes(): HasMany
    {
        return $this->hasMany(CustomerNote::class, 'business_customer_id');
    }
}
