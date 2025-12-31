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
        Schema::create('podaci_o_radnom_mestu', function (Blueprint $table) {
            // Polja iz tabele podaci_o_konkursu
            $table->id();
            $table->integer('vrsta_organa')->nullable();
            $table->integer('organ')->nullable();
            $table->string('naziv_radnog_mesta', 255)->nullable();
            $table->integer('tip_konkursa')->nullable();
            $table->integer('broj_izvrsilaca')->nullable();
            $table->integer('zvanje')->nullable();
            $table->integer('mesto_rada')->nullable();
            $table->integer('status_konkursa_na_dan_1')->nullable();
            $table->integer('status_konkursa_na_dan_2')->nullable();
            $table->date('datum_dobijanja_saglasnosti_vlade')->nullable();
            $table->date('datum_donosenja_resenja_o_pokretanju_postupka')->nullable();
            $table->date('datum_dobijanja_obavestenja_od_suka')->nullable();
            $table->date('datum_odrzavanja_prvog_sastanka')->nullable();
            $table->date('datum_oglasavanja')->nullable();
            $table->date('datum_pregleda_prijava')->nullable();
            $table->date('datum_ofk_izvestaja')->nullable()->comment('ne datum provere kandidata, već datum kreiranja izveštaja SUKa');
            $table->date('datum_pocetka_provere_pfk')->nullable();
            $table->date('datum_pk_izvestaja')->nullable()->comment('ne datum provere kandidata, već datum kreiranja izveštaja SUKa');
            $table->date('datum_predaje_dokumentacije')->nullable();
            $table->date('datum_pocetka_sprovodjenja_intervjua')->nullable();
            $table->date('datum_dostavljanja_liste_rukovodiocu_organa')->nullable();
            $table->date('datum_donosenja_resenja_o_izabranom_kandidatu')->nullable();
            $table->date('datum_stupanja_na_rad')->nullable()->comment('datum stupanja na rad prvog izvršioca');
            $table->integer('broj_primljenih_izvrsilaca')->nullable();
            $table->integer('ocena_sa_vrednovanja')->nullable();
            $table->integer('broj_zalbi_na_resenje_o_odbacaju_prijave')->nullable();
            $table->integer('broj_zalbi_na_resenje_o_prijemu_u_radni_odnos')->nullable();
            $table->integer('broj_usvojenih_zalbi_na_resenje_o_odbacaju_prijave')->nullable();
            $table->integer('broj_izvrsilaca_ponovno_oglasavanje')->nullable();

            // Polja iz tabele podaci_o_prijavama
            $table->integer('ukupan_broj_prijava')->nullable();
            $table->integer('broj_prijava_iz_organa')->nullable();
            $table->integer('broj_prijava_iz_drugih_organa')->nullable();
            $table->integer('broj_prijava_van_drzavnih_organa')->nullable();
            $table->integer('broj_validnih_prijava')->nullable();
            $table->integer('broj_validnih_prijava_iz_organa')->nullable();
            $table->integer('broj_validnih_prijava_iz_drugog_organa')->nullable();
            $table->integer('broj_validnih_prijava_van_drzavnih_organa')->nullable();
            $table->integer('broj_kandidata_koji_su_ispunlii_merila_ofk')->nullable();
            $table->integer('broj_kandidata_koji_su_ispunlii_merila_pfk')->nullable();
            $table->integer('provera_pfk')->nullable();
            $table->integer('broj_kandidata_ispunili_merila_pk')->nullable();
            $table->integer('broj_odazvanih_kandidata_na_zavrsnom_razgovoru')->nullable();
            $table->integer('broj_kandidata_na_listi')->nullable();
            $table->integer('broj_kandidata_iz_organa_na_listi')->nullable();
            $table->integer('broj_kandidata_iz_drugog_drzavnog_organa_na_listi')->nullable();
            $table->integer('broj_kandidata_van_drzavnih_organa_na_listi')->nullable();
            $table->integer('izabrani_kandidat')->nullable();
            $table->integer('broj_bodova_izabranog_kandidata_na_ofk')->nullable();
            $table->integer('broj_bodova_izabranog_kandidata_na_pfk')->nullable();
            $table->integer('broj_bodova_izabranog_kandidata_na_pk')->nullable();
            $table->integer('broj_bodova_izabranog_kandidata_na_zavrsnom_razgovoru')->nullable();
            $table->integer('drugoplasirani_kandidat')->nullable();
            $table->integer('broj_bodova_drugplasiranog_kandidata_na_ofk')->nullable();
            $table->integer('broj_bodova_drugplasiranog_kandidata_na_pfk')->nullable();
            $table->integer('broj_bodova_drugplasiranog_kandidata_na_pk')->nullable();
            $table->integer('broj_bodova_drugoplasiranog_kandidata_na_zavrsnom_razgovoru')->nullable();

            $table->engine = 'InnoDB';
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('podaci_o_radnom_mestu');
    }
};
