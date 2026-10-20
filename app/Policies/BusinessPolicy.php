<?php

namespace App\Policies;

use App\Models\Business;
use App\Models\User;
use App\Services\BusinessAccessService;

class BusinessPolicy
{
    public function __construct(
        protected BusinessAccessService $access
    ) {}

    public function viewAny(User $user): bool
    {
        if ($user->status !== 'active') {
            return false;
        }

        if ($user->hasRole('super_admin', 'web')) {
            return true;
        }

        if ($user->can('business.view')) {
            return true;
        }

        return $user->businessMemberships()
            ->where('is_active', true)
            ->exists();
    }

    public function view(
        User $user,
        Business $business
    ): bool {
        return $this->access->can(
            $user,
            $business,
            'business.view'
        );
    }

    public function create(User $user): bool
    {
        return $user->status === 'active';
    }

    public function update(
        User $user,
        Business $business
    ): bool {
        return $this->access->can(
            $user,
            $business,
            'business.update'
        );
    }

    public function delete(
        User $user,
        Business $business
    ): bool {
        return $this->access->can(
            $user,
            $business,
            'business.delete'
        );
    }
}
