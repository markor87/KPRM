<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('podaci_o_radnom_mestu', function (Blueprint $table) {
            $table->unsignedInteger('broj_usvojenih_zalbi_na_resenje_o_prijemu_u_radni_odnos')
                ->nullable()
                ->after('broj_usvojenih_zalbi_na_resenje_o_odbacaju_prijave');
        });
    }

    public function down(): void
    {
        Schema::table('podaci_o_radnom_mestu', function (Blueprint $table) {
            $table->dropColumn('broj_usvojenih_zalbi_na_resenje_o_prijemu_u_radni_odnos');
        });
    }
};
