<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class GrowyaAdvancedDashboardPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $byRole = [
            'user' => ['dashboard.customer.view','profile.self.manage','orders.self.view','business.request.create','business.request.view','business.request.update'],
            'owner' => ['dashboard.owner.view','products.view','services.view','orders.view','payments.view','returns.view','delivery.view','analytics.view'],
            'admin' => ['dashboard.admin.view','users.view','business.view','analytics.view','reports.view'],
            'super_admin' => ['dashboard.super_admin.view','roles.manage','permissions.manage','users.manage','business.verify'],
        ];
        foreach ($byRole as $name => $list) {
            $role = Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            foreach ($list as $permission) {
                Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            }
            // Add only: don't wipe permissions previously configured through admin matrix.
            $role->givePermissionTo($list);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
