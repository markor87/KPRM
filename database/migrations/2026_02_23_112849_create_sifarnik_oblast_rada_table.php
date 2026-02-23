<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sifarnik_oblast_rada', function (Blueprint $table) {
            $table->id();
            $table->string('oblast_rada', 255);

            $table->engine = 'InnoDB';
        });

        DB::table('sifarnik_oblast_rada')->insert([
            ['oblast_rada' => 'Послови руковођења'],
            ['oblast_rada' => 'Инспекцијски послови'],
            ['oblast_rada' => 'Нормативни послови'],
            ['oblast_rada' => 'Управно-правни послови'],
            ['oblast_rada' => 'Студијско-аналитички'],
            ['oblast_rada' => 'Стручно-оперативни'],
            ['oblast_rada' => 'Послови управљања ЕУ фондовима'],
            ['oblast_rada' => 'Послови међународне сарадње'],
            ['oblast_rada' => 'Финансијско-материјални'],
            ['oblast_rada' => 'Послови интерне ревизије'],
            ['oblast_rada' => 'Информатички послови'],
            ['oblast_rada' => 'Послови управљања људским ресурсима'],
            ['oblast_rada' => 'Послови јавних набавки'],
            ['oblast_rada' => 'Послови односа са јавношћу'],
            ['oblast_rada' => 'Административно технички послови'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('sifarnik_oblast_rada');
    }
};
