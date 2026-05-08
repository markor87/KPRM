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
        Schema::table('sifarnik_kodovi_gradova', function (Blueprint $table) {
            $table->string('grad')->nullable()->after('kod_grada');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sifarnik_kodovi_gradova', function (Blueprint $table) {
            $table->dropColumn('grad');
        });
    }
};
