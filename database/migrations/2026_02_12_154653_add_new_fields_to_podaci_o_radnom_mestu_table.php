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
            // OFK polja
            $table->date('datum_slanja_zahteva_za_sprovodjenje_ofk_provera')->nullable()->after('datum_pregleda_prijava');
            $table->integer('broj_kandidata_za_koje_se_zakazuju_ofk')->nullable()->after('datum_slanja_zahteva_za_sprovodjenje_ofk_provera');

            // PFK polja
            $table->date('datum_slanja_zahteva_za_sprovodjenje_pfk_provera')->nullable()->after('datum_ofk_izvestaja');
            $table->integer('broj_kandidata_za_koje_se_zakazuju_pfk')->nullable()->after('datum_slanja_zahteva_za_sprovodjenje_pfk_provera');
            $table->date('datum_pfk_izvestaja')->nullable()->after('datum_pocetka_provere_pfk');

            // PK polja
            $table->date('datum_slanja_zahteva_za_sprovodjenje_pk_provera')->nullable()->after('datum_pfk_izvestaja');
            $table->integer('broj_kandidata_za_koje_se_zakazuju_pk')->nullable()->after('datum_slanja_zahteva_za_sprovodjenje_pk_provera');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('podaci_o_radnom_mestu', function (Blueprint $table) {
            $table->dropColumn([
                'datum_slanja_zahteva_za_sprovodjenje_ofk_provera',
                'broj_kandidata_za_koje_se_zakazuju_ofk',
                'datum_slanja_zahteva_za_sprovodjenje_pfk_provera',
                'broj_kandidata_za_koje_se_zakazuju_pfk',
                'datum_pfk_izvestaja',
                'datum_slanja_zahteva_za_sprovodjenje_pk_provera',
                'broj_kandidata_za_koje_se_zakazuju_pk',
            ]);
        });
    }
};
