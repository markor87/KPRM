<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Super Admin role with all permissions
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::all());

        // Create Admin role with most permissions (no delete permissions)
        $admin = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $adminPermissions = Permission::where('name', 'not like', '%.delete')->get();
        $admin->syncPermissions($adminPermissions);

        // Create Kadrovik role with only view permissions
        $kadrovik = Role::firstOrCreate(['name' => 'Kadrovik', 'guard_name' => 'web']);
        $kadrovikPermissions = Permission::where('name', 'like', '%.view')->get();
        $kadrovik->syncPermissions($kadrovikPermissions);

        // Create User role with basic permissions
        $user = Role::firstOrCreate(['name' => 'User', 'guard_name' => 'web']);
        $userPermissions = Permission::whereIn('name', ['dashboard.view'])->get();
        $user->syncPermissions($userPermissions);

        $this->command->info('Roles created successfully with permissions assigned.');
    }
}
