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
            $table->integer('broj_uspelih_postupaka')->nullable()->after('velicina_organa');
            $table->integer('broj_neuspelih_postupaka')->nullable()->after('broj_uspelih_postupaka');
            $table->integer('broj_obustavljenih_postupaka')->nullable()->after('broj_neuspelih_postupaka');
            $table->integer('broj_ponistenih_postupaka')->nullable()->after('broj_obustavljenih_postupaka');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('podaci_o_radnom_mestu', function (Blueprint $table) {
            $table->dropColumn([
                'broj_uspelih_postupaka',
                'broj_neuspelih_postupaka',
                'broj_obustavljenih_postupaka',
                'broj_ponistenih_postupaka',
            ]);
        });
    }
};
