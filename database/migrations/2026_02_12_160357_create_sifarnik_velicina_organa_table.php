<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sifarnik_velicina_organa', function (Blueprint $table) {
            $table->id();
            $table->string('velicina_organa')->nullable();
        });

        // Populate the table with predefined values
        DB::table('sifarnik_velicina_organa')->insert([
            ['id' => 1, 'velicina_organa' => 'до 60 запослених'],
            ['id' => 2, 'velicina_organa' => '61-150'],
            ['id' => 3, 'velicina_organa' => '151-300'],
            ['id' => 4, 'velicina_organa' => '300+'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sifarnik_velicina_organa');
    }
};
