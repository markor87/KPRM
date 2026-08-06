<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Индекс на `organ` — по тој колони се филтрира сваки упит над радним местима
     * (OrganFilterService), укључујући свих 11 виџета на контролној табли.
     */
    public function up(): void
    {
        Schema::table('podaci_o_radnom_mestu', function (Blueprint $table) {
            $table->index('organ', 'idx_organ');
        });
    }

    public function down(): void
    {
        Schema::table('podaci_o_radnom_mestu', function (Blueprint $table) {
            $table->dropIndex('idx_organ');
        });
    }
};
