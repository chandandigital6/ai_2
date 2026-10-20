<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // Do not create demo privileged accounts or overwrite real passwords.
        $this->call([
            RolePermissionSeeder::class,
            BusinessPermissionSeeder::class,
            AiPlanSeeder::class,
            GrowyaSupportPermissionSeeder::class,
            GrowyaReferralSettingsSeeder::class,
            GrowyaDashboardPermissionSeeder::class,
            GrowyaAdvancedDashboardPermissionSeeder::class,
            BusinessCategorySeeder::class,
            IndiaLocationSeeder::class,

        ]);
    }
}
