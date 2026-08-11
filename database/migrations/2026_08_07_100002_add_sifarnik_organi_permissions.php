<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Дозволе за екран „Органи" (хијерархија органа и додела приступа подређеним органима).
     * Иницијално их добија само Super Admin; свакој другој улози се додељују ручно кроз
     * „Улоге → измени → Ресурси".
     */
    private const DOZVOLE = [
        'ViewAny:SifarnikOrgani',
        'View:SifarnikOrgani',
        'Update:SifarnikOrgani',
    ];

    public function up(): void
    {
        $superAdmin = Role::where('name', 'Super Admin')
            ->where('guard_name', 'web')
            ->first();

        foreach (self::DOZVOLE as $naziv) {
            $dozvola = Permission::firstOrCreate([
                'name' => $naziv,
                'guard_name' => 'web',
            ]);

            $superAdmin?->givePermissionTo($dozvola);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::whereIn('name', self::DOZVOLE)
            ->where('guard_name', 'web')
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
