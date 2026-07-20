<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Preimenovanje polja: „Статус конкурса" -> „Исход конкурса".
     * Koristi se raw SQL CHANGE (MyISAM-safe, cuva podatke i tip kolone).
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE podaci_o_radnom_mestu CHANGE status_konkursa ishod_konkursa INT NULL');
        DB::statement('ALTER TABLE podaci_o_radnom_mestu CHANGE datum_statusa_konkursa datum_ishoda_konkursa DATE NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE podaci_o_radnom_mestu CHANGE ishod_konkursa status_konkursa INT NULL');
        DB::statement('ALTER TABLE podaci_o_radnom_mestu CHANGE datum_ishoda_konkursa datum_statusa_konkursa DATE NULL');
    }
};
