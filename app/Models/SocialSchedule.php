<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialSchedule extends Model
{
    protected $table = 'social_schedules';

    protected $fillable = [
        'caption', 'image_path', 'timezone', 'created_by',
        'business_id',
        'social_account_id',
        'frequency',
        'run_time',
        'days_of_week',
        'next_run_at',
        'last_run_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'days_of_week' => 'array',
            'next_run_at' => 'datetime',
            'last_run_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function social_account(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class, 'social_account_id');
    }
}
