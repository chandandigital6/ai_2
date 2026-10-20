<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
class GrowyaDashboardPermissionSeeder extends Seeder {
 public function run(): void {
  $matrix = [
   'user' => ['dashboard.customer.view', 'profile.self.manage', 'orders.self.view', 'business.request.create', 'business.request.view', 'business.request.update'],
   'owner' => ['dashboard.owner.view', 'business.view','business.update','products.view','products.create','products.update','products.delete','services.view','services.create','services.update','services.delete','orders.view','orders.manage','customers.view','customers.manage','staff.view','staff.manage','reviews.view','reviews.manage','offers.view','offers.manage','analytics.view','gallery.view','gallery.manage','business.hours.manage','business.social.manage','payments.view','payments.verify','returns.view','returns.manage','delivery.view','delivery.manage'],
   'admin' => ['dashboard.admin.view','users.view','business.view','analytics.view','reports.view'],
   'super_admin' => ['dashboard.super_admin.view','users.view','users.manage','roles.manage','permissions.manage','business.verify','business.view','business.update','analytics.view','reports.view','settings.manage','payments.verify','returns.manage','delivery.manage'],
  ];
  foreach ($matrix as $role => $permissions) {
   $model = Role::firstOrCreate(['name'=>$role,'guard_name'=>'web']);
   foreach ($permissions as $name) Permission::firstOrCreate(['name'=>$name,'guard_name'=>'web']);
   $model->givePermissionTo($permissions); // Preserves other existing permission grants.
  }
  app(PermissionRegistrar::class)->forgetCachedPermissions();
 }
}
