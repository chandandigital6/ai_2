<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Models\Permission;

class BusinessUser extends Model
{
    protected $fillable = [
        'business_id',
        'user_id',
        'role',
        'is_active',
        'joined_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'joined_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'business_user_permissions',
            'business_user_id',
            'permission_id'
        )->withTimestamps();
    }

    public function hasBusinessPermission(
        string $permission
    ): bool {
        return $this->permissions()
            ->where('name', $permission)
            ->where('guard_name', 'web')
            ->exists();
    }
}
