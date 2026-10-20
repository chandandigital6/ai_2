<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SocialAccount extends Model
{
    protected $table = 'social_accounts';
    protected $fillable = [
        'business_id',
        'platform',
        'external_id',
        'account_name',
        'access_token',
        'refresh_token',
        'expires_at',
        'metadata',
        'connection_status',
    ];

    protected $hidden = ['access_token', 'refresh_token'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'metadata' => 'array',
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function social_post_platforms(): HasMany
    {
        return $this->hasMany(SocialPostPlatform::class, 'social_account_id');
    }

    public function social_schedules(): HasMany
    {
        return $this->hasMany(SocialSchedule::class, 'social_account_id');
    }
}
