<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Оцене са провера компетенција важе две године, па кандидат може тражити да му се
     * признају оцене са раније спроведене провере. Тада се провера не спроводи и датуми
     * за ту фазу не постоје у овом поступку.
     *
     * ОФК и ПК се признају независно једно од другог, па су два обележја. ПФК се увек
     * спроводи изнова јер је везан за конкретно радно место.
     */
    public function up(): void
    {
        Schema::table('podaci_o_radnom_mestu', function (Blueprint $table) {
            $table->boolean('ofk_ocene_preuzete')->default(false)->after('datum_ofk_izvestaja');
            $table->boolean('pk_ocene_preuzete')->default(false)->after('datum_pk_izvestaja');
        });
    }

    public function down(): void
    {
        Schema::table('podaci_o_radnom_mestu', function (Blueprint $table) {
            $table->dropColumn(['ofk_ocene_preuzete', 'pk_ocene_preuzete']);
        });
    }
};
