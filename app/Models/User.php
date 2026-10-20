<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Concerns\HasTeams;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'name',
    'email',
    'password',
    'current_team_id',
    'phone',
    'user_type',
    'status',
])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    protected $guard_name = 'web';

    protected $attributes = ['status' => 'active', 'user_type' => 'customer'];

    use HasApiTokens, HasFactory, HasTeams, Notifiable, TwoFactorAuthenticatable;
    use HasRoles {
        HasTeams::teams insteadof HasRoles;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function ownedBusinesses()
    {
        return $this->hasMany(
            Business::class,
            'owner_id'
        );
    }

    public function businessMemberships()
    {
        return $this->hasMany(
            BusinessUser::class,
            'user_id'
        );
    }

    public function businesses()
    {
        return $this->belongsToMany(
            Business::class,
            'business_users'
        )
            ->withPivot([
                'id',
                'role',
                'is_active',
                'joined_at',
            ])
            ->withTimestamps();
    }
}
