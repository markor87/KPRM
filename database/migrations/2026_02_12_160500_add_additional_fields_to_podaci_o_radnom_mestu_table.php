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
            // Polja na kraju (nova sekcija) - nakon poslednjeg postojećeg polja
            $table->integer('broj_neodazvanih_kandidata_ofk')->nullable()->after('broj_bodova_drugoplasiranog_kandidata_na_zavrsnom_razgovoru');
            $table->integer('broj_neodazvanih_kandidata_pfk')->nullable()->after('broj_neodazvanih_kandidata_ofk');
            $table->integer('broj_neodazvanih_kandidata_pk')->nullable()->after('broj_neodazvanih_kandidata_pfk');
            $table->integer('broj_neodazvanih_kandidata_dokumentacija')->nullable()->after('broj_neodazvanih_kandidata_pk');
            $table->integer('broj_neodazvanih_kandidata_zavrsni_razgovor')->nullable()->after('broj_neodazvanih_kandidata_dokumentacija');
            $table->string('oblast_rada')->nullable()->after('broj_neodazvanih_kandidata_zavrsni_razgovor');
            $table->foreignId('velicina_organa')->nullable()->constrained('sifarnik_velicina_organa')->after('oblast_rada');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('podaci_o_radnom_mestu', function (Blueprint $table) {
            $table->dropForeign(['velicina_organa']);
            $table->dropColumn([
                'broj_neodazvanih_kandidata_ofk',
                'broj_neodazvanih_kandidata_pfk',
                'broj_neodazvanih_kandidata_pk',
                'broj_neodazvanih_kandidata_dokumentacija',
                'broj_neodazvanih_kandidata_zavrsni_razgovor',
                'oblast_rada',
                'velicina_organa',
            ]);
        });
    }
};
