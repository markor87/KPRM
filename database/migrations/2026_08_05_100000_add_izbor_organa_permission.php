<?php

use App\Services\OrganFilterService;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Дозвола за избор органа на контролној табли. Super Admin је добија одмах, а
     * осталим улогама се додељује ручно (Улоге → измени → таб „Остало").
     */
    public function up(): void
    {
        $permission = Permission::firstOrCreate([
            'name' => OrganFilterService::PERMISSION_IZBOR_ORGANA,
            'guard_name' => 'web',
        ]);

        Role::where('name', 'Super Admin')
            ->where('guard_name', 'web')
            ->first()
            ?->givePermissionTo($permission);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('name', OrganFilterService::PERMISSION_IZBOR_ORGANA)
            ->where('guard_name', 'web')
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
