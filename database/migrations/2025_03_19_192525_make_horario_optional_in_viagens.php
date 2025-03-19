<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('viagens', function (Blueprint $table) {
            $table->unsignedBigInteger('horario_id')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('viagens', function (Blueprint $table) {
            $table->unsignedBigInteger('horario_id')->nullable(false)->change();
        });
    }
};