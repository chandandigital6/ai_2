<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)
            ->forgetCachedPermissions();

        $roles = [
            'super_admin',
            'admin',
            'user',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate([
                'name' => $role,
                'guard_name' => 'web',
            ]);
        }

        $permissions = [
            'business.view',
            'business.create',
            'business.update',
            'business.delete',
            'business.verify',
            'users.view',
            'users.manage',
            'plans.manage',
            'subscriptions.manage',
            'analytics.view',
            'reports.view',
            'settings.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        Role::findByName('admin', 'web')
            ->syncPermissions([
                'business.view',
                'business.verify',
                'users.view',
                'analytics.view',
                'reports.view',
            ]);

        app(PermissionRegistrar::class)
            ->forgetCachedPermissions();
    }
}
