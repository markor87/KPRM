<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oblast_rada_podaci_o_radnom_mestu', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('podaci_o_radnom_mestu_id');
            $table->unsignedBigInteger('sifarnik_oblast_rada_id');

            $table->foreign('podaci_o_radnom_mestu_id', 'fk_oblast_podaci')
                ->references('id')
                ->on('podaci_o_radnom_mestu')
                ->onDelete('cascade');

            $table->foreign('sifarnik_oblast_rada_id', 'fk_oblast_sifarnik')
                ->references('id')
                ->on('sifarnik_oblast_rada')
                ->onDelete('cascade');

            $table->unique(['podaci_o_radnom_mestu_id', 'sifarnik_oblast_rada_id'], 'unique_oblast_podaci');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oblast_rada_podaci_o_radnom_mestu');
    }
};
