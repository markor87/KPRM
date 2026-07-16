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
            $table->integer('status_konkursa')
                ->nullable()
                ->after('status_konkursa_na_dan_2');
            $table->date('datum_statusa_konkursa')
                ->nullable()
                ->after('status_konkursa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('podaci_o_radnom_mestu', function (Blueprint $table) {
            $table->dropColumn(['status_konkursa', 'datum_statusa_konkursa']);
        });
    }
};
