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
        Schema::create('mesto_rada_podaci_o_radnom_mestu', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('podaci_o_radnom_mestu_id');
            $table->unsignedBigInteger('sifarnik_mesta_id');
            $table->timestamps();

            // Foreign keys
            $table->foreign('podaci_o_radnom_mestu_id', 'fk_pivot_podaci')
                ->references('id')
                ->on('podaci_o_radnom_mestu')
                ->onDelete('cascade');

            $table->foreign('sifarnik_mesta_id', 'fk_pivot_mesta')
                ->references('id')
                ->on('sifarnik_mesta')
                ->onDelete('cascade');

            // Unique constraint - prevent duplicate combinations
            $table->unique(['podaci_o_radnom_mestu_id', 'sifarnik_mesta_id'], 'unique_mesto_podaci');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mesto_rada_podaci_o_radnom_mestu');
    }
};
