<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusinessDailyAnalytic extends Model
{
    protected $table = 'business_daily_analytics';
    protected $fillable = [
        'business_id',
        'date',
        'views',
        'followers',
        'orders',
        'enquiries',
        'clicks',
        'shares',
        'notifications_sent',
        'notifications_opened',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'views' => 'integer',
            'followers' => 'integer',
            'orders' => 'integer',
            'enquiries' => 'integer',
            'clicks' => 'integer',
            'shares' => 'integer',
            'notifications_sent' => 'integer',
            'notifications_opened' => 'integer',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }
}
