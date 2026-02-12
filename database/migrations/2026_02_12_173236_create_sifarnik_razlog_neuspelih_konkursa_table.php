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
        Schema::create('sifarnik_razlog_neuspelih_konkursa', function (Blueprint $table) {
            $table->id();
            $table->string('razlog');
        });

        // Populate with predefined values
        DB::table('sifarnik_razlog_neuspelih_konkursa')->insert([
            ['id' => 1, 'razlog' => 'Ниједна пристигла пријава на оглас'],
            ['id' => 2, 'razlog' => 'Ниједна валидна пристигла пријава'],
            ['id' => 3, 'razlog' => 'Неуспеле провере ОФК'],
            ['id' => 4, 'razlog' => 'Неуспеле провере ПФК'],
            ['id' => 5, 'razlog' => 'Неуспеле провере ПК'],
            ['id' => 6, 'razlog' => 'Недостављена или невалидна документација'],
            ['id' => 7, 'razlog' => 'Неодазивање кандидата на завршни интервју'],
            ['id' => 8, 'razlog' => 'Ниједан кандидат није изабран са коначне листе'],
            ['id' => 9, 'razlog' => 'Одустајање кандидата'],
            ['id' => 10, 'razlog' => 'Усвајање жалбе'],
            ['id' => 11, 'razlog' => 'Кандидат није ступио на рад'],
            ['id' => 12, 'razlog' => 'Влади није предложен кандидат за постављење на položај'],
            ['id' => 13, 'razlog' => 'Влада није поставила предложеног кандидата у року од 30 дана од достављања предлога'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sifarnik_razlog_neuspelih_konkursa');
    }
};
