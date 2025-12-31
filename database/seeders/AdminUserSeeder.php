<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Create or update Super Admin user
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@kprm.rs'],
            [
                'name' => 'Super Administrator',
                'password' => Hash::make('password123'),
            ]
        );
        $superAdmin->syncRoles(['Super Admin']);
        $this->command->info('Super Admin user created/updated: admin@kprm.rs');

        // Update existing marko.radovanovic user with Super Admin role
        $marko = User::where('email', 'marko.radovanovic@suk.gov.rs')->first();
        if ($marko) {
            $marko->syncRoles(['Super Admin']);
            $this->command->info('User marko.radovanovic@suk.gov.rs assigned Super Admin role.');
        }

        // Assign Super Admin role to all users with is_super_admin = true
        $superAdminUsers = User::where('is_super_admin', true)->get();
        foreach ($superAdminUsers as $user) {
            $user->syncRoles(['Super Admin']);
        }
        $this->command->info("Assigned Super Admin role to {$superAdminUsers->count()} users with is_super_admin flag.");

        // Create test users
        $testAdmin = User::firstOrCreate(
            ['email' => 'test.admin@kprm.rs'],
            [
                'name' => 'Test Admin',
                'password' => Hash::make('password123'),
            ]
        );
        $testAdmin->syncRoles(['Admin']);

        $testKadrovik = User::firstOrCreate(
            ['email' => 'test.kadrovik@kprm.rs'],
            [
                'name' => 'Test Kadrovik',
                'password' => Hash::make('password123'),
            ]
        );
        $testKadrovik->syncRoles(['Kadrovik']);

        $this->command->info('Admin users created/updated successfully.');
    }
}
