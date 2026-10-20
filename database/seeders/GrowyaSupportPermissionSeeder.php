<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
class GrowyaSupportPermissionSeeder extends Seeder {
 public function run(): void {
  $p=Permission::firstOrCreate(['name'=>'support.tickets.manage','guard_name'=>'web']);
  $role=Role::findOrCreate('super_admin','web');$role->givePermissionTo($p);
  app(PermissionRegistrar::class)->forgetCachedPermissions();
 }
}
