<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Правна хијерархија органа: управа у саставу стоји под својим министарством.
     * Попуњава се ручно кроз екран „Органи" — намерно без миграције са подацима, јер
     * се ID-јеви органа не поклапају између тестне и продукционе базе.
     *
     * Сама хијерархија НЕ даје приступ — она само одређује који су парови министарство ↔
     * управа уопште могући. Право се додељује изричито, у табели organ_pristupi.
     */
    public function up(): void
    {
        Schema::table('sifarnik_organi', function (Blueprint $table) {
            $table->unsignedBigInteger('nadredjeni_organ_id')->nullable()->after('vrsta_organ_id');
            $table->foreign('nadredjeni_organ_id')
                ->references('id')
                ->on('sifarnik_organi')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('sifarnik_organi', function (Blueprint $table) {
            $table->dropForeign(['nadredjeni_organ_id']);
            $table->dropColumn('nadredjeni_organ_id');
        });
    }
};
