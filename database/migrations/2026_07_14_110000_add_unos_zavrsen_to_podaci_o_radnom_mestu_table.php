<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('podaci_o_radnom_mestu', function (Blueprint $table) {
            $table->boolean('unos_zavrsen')
                ->default(false)
                ->after('udeo_kandidata_mladjih_od_30');

            $table->timestamp('unos_zavrsen_at')
                ->nullable()
                ->after('unos_zavrsen');

            // Bez stranog kljuca: tabela `users` je MyISAM, koji FK ne podrzava.
            $table->unsignedBigInteger('unos_zavrsen_by')
                ->nullable()
                ->after('unos_zavrsen_at');
        });
    }

    public function down(): void
    {
        Schema::table('podaci_o_radnom_mestu', function (Blueprint $table) {
            $table->dropColumn(['unos_zavrsen', 'unos_zavrsen_at', 'unos_zavrsen_by']);
        });
    }
};
