<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MyISAM — no real FK constraints, drop index and unique key manually
        DB::statement('ALTER TABLE mesto_rada_podaci_o_radnom_mestu DROP INDEX unique_mesto_podaci');
        DB::statement('ALTER TABLE mesto_rada_podaci_o_radnom_mestu DROP INDEX fk_pivot_mesta');
        DB::statement('ALTER TABLE mesto_rada_podaci_o_radnom_mestu DROP COLUMN sifarnik_mesta_id');

        Schema::table('mesto_rada_podaci_o_radnom_mestu', function (Blueprint $table) {
            $table->unsignedBigInteger('sifarnik_kodovi_gradova_id')->nullable()->after('podaci_o_radnom_mestu_id');
            $table->string('region')->nullable()->after('sifarnik_kodovi_gradova_id');
            $table->string('oblast')->nullable()->after('region');
            $table->string('kod_grada')->nullable()->after('oblast');
        });
    }

    public function down(): void
    {
        Schema::table('mesto_rada_podaci_o_radnom_mestu', function (Blueprint $table) {
            $table->dropColumn(['sifarnik_kodovi_gradova_id', 'region', 'oblast', 'kod_grada']);
            $table->unsignedBigInteger('sifarnik_mesta_id')->after('podaci_o_radnom_mestu_id');
        });
    }
};
