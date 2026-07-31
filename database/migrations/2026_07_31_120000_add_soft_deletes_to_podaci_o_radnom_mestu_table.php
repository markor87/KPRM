<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('podaci_o_radnom_mestu', function (Blueprint $table) {
            // Soft delete: obrisani zapisi ostaju u bazi sa popunjenim deleted_at
            // i mogu se vratiti. Tabela nema created_at/updated_at (timestamps=false),
            // ali SoftDeletes koristi samo deleted_at, pa je nezavisno od toga.
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('podaci_o_radnom_mestu', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
