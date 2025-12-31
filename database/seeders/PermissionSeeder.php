<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $modules = config('modules.modules', []);

        foreach ($modules as $moduleKey => $module) {
            foreach ($module['permissions'] as $permission) {
                Permission::firstOrCreate([
                    'name' => "{$moduleKey}.{$permission}",
                    'guard_name' => 'web',
                ]);
            }
        }

        $this->command->info('Permissions created successfully from modules config.');
    }
}
