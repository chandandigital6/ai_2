<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\BusinessStaffAudit;
use App\Models\BusinessUser;
use App\Models\User;
use App\Services\BusinessAccessService;
use App\Services\GrowyaPlanLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;

class BusinessStaffController extends Controller
{
    private function authorizeManager(Business $business, string $permission = 'staff.manage'): void
    {
        abort_unless(auth()->user() && app(BusinessAccessService::class)->can(auth()->user(), $business, $permission), 403);
    }

    private function roles(): array
    {
        return ['admin' => 'Business Admin', 'manager' => 'Manager', 'staff' => 'Staff', 'marketing' => 'Marketing', 'sales' => 'Sales'];
    }

    private function owner(Business $business): bool
    {
        return auth()->user()->hasRole('super_admin', 'web') || (int) $business->owner_id === (int) auth()->id();
    }

    private function permissions(Business $business)
    {
        $query = Permission::where('guard_name', 'web')->whereIn('name', config('business_permissions.permissions'));
        if (! $this->owner($business)) {
            $ids = app(BusinessAccessService::class)->membership(auth()->user(), $business)?->permissions()->pluck('permissions.id')->all() ?? [];
            $query->whereIn('id', $ids);
        }

        return $query->orderBy('name')->get(['id', 'name']);
    }

    private function ids(Request $request, Business $business): array
    {
        $ids = array_map('intval', $request->input('permissions', []));
        if (array_diff($ids, $this->permissions($business)->pluck('id')->all())) {
            throw ValidationException::withMessages(['permissions' => 'You may assign only business permissions you hold in this business.']);
        }

        return array_values(array_unique($ids));
    }

    private function checkMember(Business $business, BusinessUser $member): void
    {
        abort_unless((int) $member->business_id === (int) $business->id, 404);
        abort_if($member->role === 'owner' || (int) $member->user_id === (int) $business->owner_id, 403, 'Owner is protected.');
        if (! $this->owner($business)) {
            abort_if((int) $member->user_id === (int) auth()->id(), 403, 'You cannot change your own access.');
            abort_if(array_diff($member->permissions()->pluck('permissions.id')->all(), $this->permissions($business)->pluck('id')->all()), 403, 'This member holds permissions outside your authority.');
        }
    }

    private function audit(Business $business, BusinessUser $member, string $action, ?array $before = null): void
    {
        DB::table('business_staff_audits')->insert([
            'business_id' => $business->id, 'actor_user_id' => auth()->id(), 'subject_user_id' => $member->user_id,
            'action' => $action, 'before_data' => $before ? json_encode($before) : null,
            'after_data' => $action === 'removed' ? null : json_encode(['role' => $member->role, 'is_active' => $member->is_active, 'permissions' => $member->permissions()->pluck('name')->all()]),
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function index(Business $business)
    {
        $this->authorizeManager($business, 'staff.view');
        $staff = $business->businessUsers()->with(['user:id,name,email,phone', 'permissions'])->orderBy('id')->paginate(15);
        $roles = $this->roles();
        $permissions = $this->permissions($business);
        $canManage = app(BusinessAccessService::class)->can(auth()->user(), $business, 'staff.manage');

        return view('businesses.staff.index', compact('business', 'staff', 'roles', 'permissions', 'canManage'));
    }

    public function store(Request $request, Business $business)
    {
        $this->authorizeManager($business);
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'], 'name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'regex:/^[6-9][0-9]{9}$/'],
            'password' => ['nullable', 'string', 'min:12', 'confirmed'],
            'role' => ['required', Rule::in(array_keys($this->roles()))],
            'permissions' => ['sometimes', 'array'], 'permissions.*' => ['integer', 'distinct'],
        ]);
        $ids = $this->ids($request, $business);
        DB::transaction(function () use ($business, $data, $ids) {
            Business::whereKey($business->id)->lockForUpdate()->firstOrFail();
            $user = User::where('email', $data['email'])->first();
            if (! $user) {
                validator($data, [
                    'name' => ['required', 'string', 'max:255'], 'phone' => ['required', Rule::unique('users', 'phone')],
                    'password' => ['required', 'string', 'min:12'],
                ])->validate();
                $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'], 'password' => $data['password'], 'user_type' => 'staff', 'status' => 'active']);
                // No global owner/admin role is granted by business membership.
            }
            if ($user->status !== 'active' || $user->hasRole('super_admin', 'web') || (int) $business->owner_id === (int) $user->id) {
                throw ValidationException::withMessages(['email' => 'Choose an active non-owner, non-super-admin account.']);
            }
            if ($business->businessUsers()->where('user_id', $user->id)->exists()) {
                throw ValidationException::withMessages(['email' => 'This account is already a member.']);
            }
            app(GrowyaPlanLimitService::class)->assertCanAdd($business, 'max_staff');
            $member = $business->businessUsers()->create(['user_id' => $user->id, 'role' => $data['role'], 'is_active' => true, 'joined_at' => now()]);
            $member->permissions()->sync($ids);
            $this->audit($business, $member, 'created');
        });

        return back()->with('success', 'Staff account added. Existing account credentials are never changed.');
    }

    public function update(Request $request, Business $business, BusinessUser $member)
    {
        $this->authorizeManager($business);
        $data = $request->validate(['role' => ['required', Rule::in(array_keys($this->roles()))], 'is_active' => ['required', 'boolean'], 'permissions' => ['sometimes', 'array'], 'permissions.*' => ['integer', 'distinct']]);
        $ids = $this->ids($request, $business);
        DB::transaction(function () use ($business, $member, $data, $ids) {
            Business::whereKey($business->id)->lockForUpdate()->firstOrFail();
            $member = BusinessUser::whereKey($member->id)->lockForUpdate()->firstOrFail();
            $this->checkMember($business, $member);
            $before = ['role' => $member->role, 'is_active' => $member->is_active, 'permissions' => $member->permissions()->pluck('name')->all()];
            if (! $member->is_active && $data['is_active']) {
                app(GrowyaPlanLimitService::class)->assertCanAdd($business, 'max_staff');
            }
            $member->update(['role' => $data['role'], 'is_active' => (bool) $data['is_active']]);
            $member->permissions()->sync($ids);
            $this->audit($business, $member, 'updated', $before);
        });

        return back()->with('success', 'Staff permissions updated.');
    }

    public function destroy(Business $business, BusinessUser $member)
    {
        $this->authorizeManager($business);
        DB::transaction(function () use ($business, $member) {
            Business::whereKey($business->id)->lockForUpdate()->firstOrFail();
            $member = BusinessUser::whereKey($member->id)->lockForUpdate()->firstOrFail();
            $this->checkMember($business, $member);
            $this->audit($business, $member, 'removed', ['role' => $member->role, 'permissions' => $member->permissions()->pluck('name')->all()]);
            $member->permissions()->detach();
            $member->delete();
        });

        return back()->with('success', 'Staff removed from this business.');
    }

    public function history(Business $business)
    {
        $this->authorizeManager($business, 'staff.view');
        $audits = BusinessStaffAudit::with(['actor', 'subject'])->where('business_id',$business->id)->orderByDesc('id')->paginate(25);

        return view('businesses.staff.history',compact('business','audits'));
    }
}
