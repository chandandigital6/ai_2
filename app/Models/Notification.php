<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Notification extends Model
{
    protected $table = 'notifications';
    protected $fillable = [
        'business_id',
        'type',
        'title',
        'message',
        'image',
        'action_url',
        'target_type',
        'scheduled_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function notification_recipients(): HasMany
    {
        return $this->hasMany(NotificationRecipient::class, 'notification_id');
    }
}
