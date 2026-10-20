<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class BusinessPermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (array_merge(config('business_permissions.permissions'), config('business_permissions.platform_permissions')) as $name) {
            Permission::findOrCreate($name, 'web');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
