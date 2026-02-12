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
        Schema::table('mesto_rada_podaci_o_radnom_mestu', function (Blueprint $table) {
            $table->integer('broj_izvrsilaca')->default(1)->after('sifarnik_mesta_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mesto_rada_podaci_o_radnom_mestu', function (Blueprint $table) {
            $table->dropColumn('broj_izvrsilaca');
        });
    }
};
