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
            $table->foreignId('razlog_neuspelog_konkursa')
                ->nullable()
                ->after('status_konkursa_na_dan_1')
                ->constrained('sifarnik_razlog_neuspelih_konkursa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('podaci_o_radnom_mestu', function (Blueprint $table) {
            $table->dropForeign(['razlog_neuspelog_konkursa']);
            $table->dropColumn('razlog_neuspelog_konkursa');
        });
    }
};
