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
        Schema::table('sifarnik_zvanje', function (Blueprint $table) {
            $table->unsignedBigInteger('organ_id')->nullable()->after('zvanje');
            $table->foreign('organ_id')->references('id')->on('sifarnik_organi')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sifarnik_zvanje', function (Blueprint $table) {
            $table->dropForeign(['organ_id']);
            $table->dropColumn('organ_id');
        });
    }
};
