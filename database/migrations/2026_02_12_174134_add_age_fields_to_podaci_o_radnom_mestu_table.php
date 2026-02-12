<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('podaci_o_radnom_mestu', function (Blueprint $table) {
            $table->decimal('prosecna_starost_kandidata', 5, 2)->nullable()->after('broj_ponistenih_postupaka');
            $table->decimal('udeo_kandidata_mladjih_od_30', 5, 2)->nullable()->after('prosecna_starost_kandidata');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('podaci_o_radnom_mestu', function (Blueprint $table) {
            $table->dropColumn([
                'prosecna_starost_kandidata',
                'udeo_kandidata_mladjih_od_30',
            ]);
        });
    }
};
