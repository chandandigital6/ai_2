<?php

namespace App\Services;

use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\User;

class BusinessAccessService
{
    public function canManageStaff(User $user, Business $business): bool
    {
        if ($user->status !== 'active') {
            return false;
        }

        if ($user->hasRole('super_admin', 'web')) {
            return true;
        }

        return $this->can($user, $business, 'staff.manage');
    }

    public function membership(User $user, Business $business): ?BusinessUser
    {
        return BusinessUser::query()
            ->where('business_id', $business->id)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();
    }

    public function can(User $user, Business $business, string $permission): bool
    {
        if ($user->status !== 'active') {
            return false;
        }

        if ($user->hasRole('super_admin', 'web')) {
            return true;
        }

        if ($user->status !== 'active' || $business->status !== 'active') {
            return false;
        }

        $isOwner = (int) $business->owner_id === (int) $user->id;
        $isApplicationPermission = in_array($permission, ['business.view', 'business.update', 'staff.view', 'staff.manage', 'categories.view', 'categories.manage', 'attributes.view', 'attributes.manage', 'documents.view', 'documents.manage'], true);

        // Pending or rejected businesses are application-only, not operational.
        if ($business->verification_status !== 'approved' && ! $isApplicationPermission) {
            return false;
        }

        // The recorded owner owns this business, even if an older record is
        // missing its business_users membership. Role alone never grants access.
        if ($isOwner) {
            return true;
        }

        // Staff must have an active membership and specifically granted
        // business-scoped permission; global Spatie role is insufficient.
        $member = $this->membership($user, $business);

        if (! $member) {
            return false;
        }

        if ($business->verification_status !== 'approved' && ! $isApplicationPermission) {
            return false;
        }

        if ($permission === 'business.view') {
            return true;
        }

        return $member->hasBusinessPermission($permission);
    }
}
