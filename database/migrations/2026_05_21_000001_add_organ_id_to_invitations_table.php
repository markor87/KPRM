<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invitations', function (Blueprint $table) {
            $table->unsignedBigInteger('organ_id')->nullable()->after('invited_by');
            $table->foreign('organ_id')->references('id')->on('sifarnik_organi')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('invitations', function (Blueprint $table) {
            $table->dropForeign(['organ_id']);
            $table->dropColumn('organ_id');
        });
    }
};
