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
            $table->dropForeign(['velicina_organa']);
            $table->dropColumn([
                'velicina_organa',
                'broj_uspelih_postupaka',
                'broj_neuspelih_postupaka',
                'broj_obustavljenih_postupaka',
                'broj_ponistenih_postupaka',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('podaci_o_radnom_mestu', function (Blueprint $table) {
            $table->unsignedBigInteger('velicina_organa')->nullable();
            $table->integer('broj_uspelih_postupaka')->nullable();
            $table->integer('broj_neuspelih_postupaka')->nullable();
            $table->integer('broj_obustavljenih_postupaka')->nullable();
            $table->integer('broj_ponistenih_postupaka')->nullable();
        });
    }
};
