<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class GrowyaRoleMatrixController extends Controller
{
    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->hasRole('super_admin'), 403);
    }

    public function index(Request $request)
    {
        $this->authorizeAdmin($request);
        $roles = Role::query()->where('guard_name', 'web')->with('permissions')->orderBy('name')->get();
        $permissions = Permission::query()->where('guard_name', 'web')->orderBy('name')->get()->groupBy(fn ($p) => explode('.', $p->name)[0]);
        return view('growya-permissions.matrix', compact('roles', 'permissions'));
    }

    public function update(Request $request, Role $role)
    {
        $this->authorizeAdmin($request);
        abort_unless($role->guard_name === 'web', 404);
        abort_if($role->name === 'super_admin', 403, 'Super admin role is protected.');
        $data = $request->validate([
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['integer', Rule::exists('permissions','id')->where('guard_name', 'web')],
        ]);
        $permissions = Permission::where('guard_name', 'web')->whereIn('id', $data['permissions'] ?? [])->get();
        DB::transaction(fn() => $role->syncPermissions($permissions));
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        return redirect()->route('growya.roles.matrix')->with('success', 'Permissions updated for '.$role->name);
    }
}
