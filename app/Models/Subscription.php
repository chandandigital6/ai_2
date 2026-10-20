<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    protected $table = 'subscriptions';
    protected $fillable = [
        'business_id',
        'plan_id',
        'billing_cycle',
        'amount',
        'starts_at',
        'ends_at',
        'trial_ends_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'trial_ends_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    // Isko 'payments' naam dein
    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class, 'subscription_id');
    }

    // Optional: Agar purane code me kahi 'subscription_payments' use hua ho toh backward compatibility ke liye
    public function subscription_payments(): HasMany
    {
        return $this->payments();
    }
}