<?php

use App\Filament\Pages\UvozRadnihMesta;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Дозвола за страницу „Увоз радних места". Super Admin је добија одмах, а осталим
     * улогама се додељује ручно (Улоге → измени → таб „Остало").
     *
     * Дозвола се уписује изричито јер config/filament-shield.php има
     * super_admin.define_via_gate => false.
     */
    public function up(): void
    {
        $permission = Permission::firstOrCreate([
            'name' => UvozRadnihMesta::PERMISSION,
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
        Permission::where('name', UvozRadnihMesta::PERMISSION)
            ->where('guard_name', 'web')
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
