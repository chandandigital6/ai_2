<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class GrowyaUserRoleController extends Controller
{
    private function ensureSuperAdmin(Request $request): void
    {
        abort_unless($request->user()?->hasRole('super_admin'), 403);
    }

    public function index(Request $request)
    {
        $this->ensureSuperAdmin($request);

        $search = trim((string) $request->query('search', ''));
        $users = User::query()->with('roles')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest('id')->paginate(20)->withQueryString();

        $roles = Role::query()->where('guard_name', 'web')
            ->where('name', '!=', 'super_admin')
            ->with('permissions')->orderBy('name')->get();

        return view('growya-user-roles.index', compact('users', 'roles', 'search'));
    }

    public function update(Request $request, User $user)
    {
        $this->ensureSuperAdmin($request);

        // The privileged role is deliberately not assignable through this screen.
        abort_if($user->hasRole('super_admin'), 403, 'Super admin accounts are protected.');
        abort_if($request->user()->is($user), 403, 'You cannot change your own role.');

        $validated = $request->validate([
            'role_id' => [
                'required', 'integer',
                Rule::exists('roles', 'id')->where(function ($query) {
                    $query->where('guard_name', 'web')->where('name', '!=', 'super_admin');
                }),
            ],
        ]);

        $role = Role::query()->where('guard_name', 'web')->findOrFail($validated['role_id']);

        DB::transaction(function () use ($user, $role) {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());
            abort_if($lockedUser->hasRole('super_admin'), 403, 'Super admin accounts are protected.');
            $lockedUser->syncRoles([$role]);
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', "Role updated for {$user->name} to {$role->name}. Role permissions are inherited automatically.");
    }
}
