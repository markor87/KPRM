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
        // First, set all existing vrsta_organ_id to NULL to avoid foreign key constraint errors
        DB::table('sifarnik_organi')->update(['vrsta_organ_id' => null]);

        Schema::table('sifarnik_organi', function (Blueprint $table) {
            // Change vrsta_organ_id to unsignedBigInteger and add foreign key
            $table->unsignedBigInteger('vrsta_organ_id')->nullable()->change();
            $table->foreign('vrsta_organ_id')
                ->references('id')
                ->on('sifarnik_vrsta_organa')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sifarnik_organi', function (Blueprint $table) {
            $table->dropForeign(['vrsta_organ_id']);
        });
    }
};
