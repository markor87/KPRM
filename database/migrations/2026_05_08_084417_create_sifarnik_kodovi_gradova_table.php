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
        Schema::create('sifarnik_kodovi_gradova', function (Blueprint $table) {
            $table->id();
            $table->string('region')->nullable();
            $table->string('oblast')->nullable();
            $table->string('kod_grada')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sifarnik_kodovi_gradova');
    }
};
