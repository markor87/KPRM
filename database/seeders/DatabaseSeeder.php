<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * ПАЖЊА: овде се не сме додавати ниједан сидер који креира кориснике или додељује
     * улоге. Раније су то радили AdminUserSeeder (правио налоге са лозинком у коду) и
     * RoleSeeder (додељивао дозволе по обрасцу који не одговара Shield именовању, па је
     * улози „Admin" давао све дозволе). Обрисани су — улоге и дозволе се од сада воде
     * искључиво кроз екран „Улоге" у апликацији.
     *
     * Први Super Admin на новој инсталацији:
     *
     *   php artisan shield:generate --all      // направи дозволе и полисе
     *   php artisan make:filament-user         // интерактивно: име, е-адреса, лозинка
     *   php artisan shield:super-admin --user=<ID>
     *
     * Лозинка се уноси у промпту и нигде се не записује. Орган се кориснику додели
     * накнадно кроз екран „Корисници".
     *
     * Шифарници се не сеју — попуњени су у бази. Изузетак су једнократни сидери за
     * кодове градова, који се по потреби покрећу ручно (`db:seed --class=...`).
     */
    public function run(): void
    {
        //
    }
}
