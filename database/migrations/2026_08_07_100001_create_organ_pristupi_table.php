<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Изричита додела права надређеном органу над подређеним. Постојање врсте значи право
     * прегледа; прекидачи додају креирање, измену и брисање.
     *
     * Правило слагања: улога даје ШТА корисник сме, ова табела даје ГДЕ. Оба морају рећи
     * „да" — зато брисање има засебан прекидач, да укључивање дозволе Delete кроз Улоге
     * не отвори брисање по свим подређеним органима одједном.
     */
    public function up(): void
    {
        Schema::create('organ_pristupi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('nadredjeni_organ_id');
            $table->unsignedBigInteger('podredjeni_organ_id');
            $table->boolean('moze_kreiranje')->default(false);
            $table->boolean('moze_izmenu')->default(false);
            $table->boolean('moze_brisanje')->default(false);
            $table->timestamps();

            $table->foreign('nadredjeni_organ_id')
                ->references('id')
                ->on('sifarnik_organi')
                ->onDelete('cascade');
            $table->foreign('podredjeni_organ_id')
                ->references('id')
                ->on('sifarnik_organi')
                ->onDelete('cascade');

            $table->unique(['nadredjeni_organ_id', 'podredjeni_organ_id'], 'organ_pristupi_par_unique');

            $table->engine = 'InnoDB';
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organ_pristupi');
    }
};
